<?php


namespace App\Services;


use Dbus;


class BluetoothException extends \Exception
{
}

class DeviceNotPairedException extends BluetoothException
{
}

class ServiceResolutionTimeout extends BluetoothException
{
}

class Bluetooth
{


    # Dbus is implemented by a C based language extension
    # there is no API documentation but there are some examples at https://github.com/derickr/pecl-dbus/tree/master/examples
    # and conference talk slides at https://derickrethans.nl/talks/dbus-ipc10s.pdf

    # The utility D-Feet is useful for exploring dbus
    # Bustle can record and display traffic

    # The GattCharacteristic1 and GattService1 API is documented at https://git.kernel.org/pub/scm/bluetooth/bluez.git/tree/doc/gatt-api.txt
    # The Device1 API is documented at https://git.kernel.org/pub/scm/bluetooth/bluez.git/tree/doc/device-api.txt

    const SERVICE_RESOLVE_TIMEOUT_SECONDS = 10;

    private Dbus $bus;


    public function __construct()
    {
        $this->bus = new Dbus(Dbus::BUS_SYSTEM); # should be (practically) infallible, if system dbus is down then things are very wrong
    }

    private function GetBluezState()
    {

        $proxy = $this->bus->createProxy("org.bluez", "/", "org.freedesktop.DBus.ObjectManager");
        # this method gets all the object paths and their current properties.
        # TODO: build object paths via recursive introspection (https://unix.stackexchange.com/a/232540/412165) and access individual properties, for efficiency
        return $proxy->GetManagedObjects()->getData(); # documented https://dbus.freedesktop.org/doc/dbus-specification.html#standard-interfaces-objectmanager
    }

    private function WaitForServiceResolution(String $objectPath){
        # The dbus library doesn't natively support accessing properties
        # we must instead invoke the dbus property methods to get property values
        $proxy = $this->bus->createProxy("org.bluez", $objectPath, "org.freedesktop.DBus.Properties");
        # we could in theory listen for the property changed signal and check if the ServicesResolved was changed
        # However, the php dbus library doesn't allow for registering signal handlers on objects
        # nor can we cancel a handler. We would be catching every property change of every bluetooth
        # device for the duration of the program
        $timeoutCounter = self::SERVICE_RESOLVE_TIMEOUT_SECONDS * 1000;
        while(!$proxy->Get("org.bluez.Device1", "ServicesResolved")->getData()){
            usleep(100 * 1000); # 100ms
            $timeoutCounter -= 100;
            if ($timeoutCounter < 0){
                $mac = $proxy->Get("org.bluez.Device1", "Address")->getData();
                throw new ServiceResolutionTimeout("Failed to resolve bluetooth services for " . $mac . " in " . self::SERVICE_RESOLVE_TIMEOUT_SECONDS . " seconds");
            }
        }
    }

    private function Connect(string $mac): void
    {
        $state = $this->GetBluezState();
        $unconnectedDevices = [];
        # Bluez publishes objects for each device, service of that device and characteristic of the service
        foreach ($state as $objectPath => $dbusInterfaceDict) {
            $interfaces = $dbusInterfaceDict->getData();
            if (!array_key_exists("org.bluez.Device1", $interfaces)) {
                continue;
            }
            $properties = $interfaces["org.bluez.Device1"]->getData();
            if (strtolower($properties["Address"]->getData()) !== strtolower($mac)) {
                continue;
            }
            # we found an instance of the device, it could be associated with multiple adapters
            if ($properties["Connected"]->getData()) {
                if(!$properties["ServicesResolved"]->getData()){
                    $this->WaitForServiceResolution($objectPath);
                }
                return; # already connected and ready
            }
            # we can only connect (usefully) if paired
            if ($properties["Paired"]->getData()) {
                $unconnectedDevices[] = $objectPath;
            }
        }
        # none of the instances of the device (if there are any) are connected
        if (count($unconnectedDevices) == 0) {
            throw new DeviceNotPairedException($mac . " had not been paired and can't be connected to");
        }

        $proxy = $this->bus->createProxy("org.bluez", $unconnectedDevices[0], "org.bluez.Device1");
        $proxy->Connect();
        $this->WaitForServiceResolution($unconnectedDevices[0]);
    }

    private function GetCharacteristicProxy(string $mac, string $serviceUUID, string $characteristicUUID): ?\DbusObject
    {
        $state = $this->GetBluezState();
        foreach ($state as $objectPath => $dbusInterfaceDict) {
            $interfaces = $dbusInterfaceDict->getData();
            if (!array_key_exists("org.bluez.GattCharacteristic1", $interfaces)) {
                continue;
            }
            $properties = $interfaces["org.bluez.GattCharacteristic1"]->getData();
            if ($properties["UUID"]->getData() !== $characteristicUUID) {
                continue;
            }
            $servicePath = $properties["Service"]->getData()->getData();
            $serviceProps = $state[$servicePath]->getData()["org.bluez.GattService1"]->getData();
            if ($serviceProps["UUID"]->getData() !== $serviceUUID) {
                continue;
            }

            $devicePath = $serviceProps["Device"]->getData()->getData();
            $deviceProps = $state[$devicePath]->getData()["org.bluez.Device1"]->getData();
            if (strtolower($deviceProps["Address"]->getData()) !== strtolower($mac)) {
                continue;
            }
            return $this->bus->createProxy("org.bluez", $objectPath, "org.bluez.GattCharacteristic1");
        }
        return null;
    }

    # I've considered creating a device class (with a mac) than provides service classes (with service uuid) that provide
    # characteristic classes (with characteristic uuid). This would provide a simple read, write function
    # however, either the model has to reconstruct these objects every call saving nothing in typing
    # or it has to reconstruct the object every time the mac changes
    public function WriteCharacteristic(string $mac, string $serviceUUID, string $characteristicUUID, array $data)
    {
        $this->Connect($mac);
        $proxy = $this->GetCharacteristicProxy($mac, $serviceUUID, $characteristicUUID);
        $wrappedData = [];
        foreach ($data as $element) {
            $wrappedData[] = new \DBusByte($element);
        }
        $proxy->WriteValue(new \DBusArray(DBus::BYTE, $wrappedData), new \DBusDict(DBus::VARIANT, array()));
    }

    public function ReadCharacteristic(string $mac, string $serviceUUID, string $characteristicUUID): array
    {
        $this->Connect($mac);
        $proxy = $this->GetCharacteristicProxy($mac, $serviceUUID, $characteristicUUID);
        return $proxy->ReadValue(new \DBusDict(DBus::VARIANT, array()))->getData();
    }
}
