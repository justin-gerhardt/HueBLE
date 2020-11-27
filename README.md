An Initial proof of concept  laravel server for controlling bluetooth phillips hue lightbulbs.

Bulbs must already be paired.

Pairing is unreliable.\
You may have to turn the bulb off and pair just after it comes on.\
You may have to use the hue app and go to the setup a voice assistant flow to get the bulb pairable.\
I had to reset the bulbs (changing the MAC addresses) to get them to pair.


currently, bulbs can be added by sending a POST request to /api/bulbs with the format {"mac": \<bulb mac address\>}\
The current state of the bulb can be seen by GET requesting /api/bulbs/\<id\>/lit\
The state can be set by making a POST request to that path with the json payload {"lit": \<true|false\> }


This project exclusively works on linux.\
It requires the [pecl D-Bus extension](https://github.com/derickr/pecl-dbus). \
If you have the good taste to be using Nix, a shell.nix file has been provided that sets up the required php environment if you run nix-shell
