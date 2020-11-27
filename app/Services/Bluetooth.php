<?php


namespace App\Services;


use Dbus;


class BluetoothException extends \Exception
{
}

class DeviceNotPairedException extends BluetoothException
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
                return; # already connected
            }

            # we can only connect if paired
            if ($properties["Paired"]->getData()) {
                $unconnectedDevices[] = $objectPath;
            }
        }
        # none of the instances of the device (if there are any) are connected
        if (count($unconnectedDevices) == 0) {
            throw new DeviceNotPairedException($mac . " had not been paired and can't be connected to");
        }

        $proxu = $this->bus->createProxy("org.bluez", $unconnectedDevices[0], "org.bluez.Device1");
        $proxu->Connect();
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
        return NULL;
    }

    # TODO: encapsulate
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
