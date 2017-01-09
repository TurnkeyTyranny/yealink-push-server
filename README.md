# Yealink XML Push Server 

I wrote this code so that I could be notified when other phones in my pool have their DND button pressed. The Phone pings my server which hosts this script, and then the script notifies my other phones by displaying a message on screen and updating the corresponding line LED to blinking red.

![yealink](https://github.com/TurnkeyTyranny/yealink-push-server/raw/master/yealink-dispatch.jpg)

![yealink 2](https://github.com/TurnkeyTyranny/yealink-push-server/raw/master/example%20dnd%20mode.jpg)


## Getting Started

Take a copy of ping.php and place it into an accessible place on your server. In the file ping.php I have blanked out some information such as my public IP addresses my phones live at, the public IP address of my server and the names of my phones.

zzz.zzz.zzz.zzz : Replace with the public IP of your server, running apache and hosting ping.php

xxx.xxx.xxx.xxx : Replace with the public IP of your phone

yyy.yyy.yyy.yyy : Replace with the public IP of your phone

### Prerequisites

What things you need to install the software and how to install them

```
This guide assumes that you have a few Yealink phones on your network. Each phone runs its own web server on port 80. You will need to forward an external port on your router (I chose 12733) to the internal port 80 of the phone. Choose a different port for each phone if they live on the same IP. This then allows you to access the phone via the internet by visiting http://zzz.zzz.zzz.zzz:12733 and viewing the phone's web server. 

Change the passwords on your phones! They will be accessible to the world.
```

![yealink port forward](https://github.com/TurnkeyTyranny/yealink-push-server/raw/master/port-forwarded-phone.png)

### Installing

```
Place ping.php on your server, somewhere you can navigate to via a web request.
```

```
Configure your phone details in ping.php. I have it setup with 2 phones for example, Bob and Alice. Each phone displays the other phone on LINE1 (Button 1) via a BLF DSS key so I can see when a phone is on a call Etc. This is configured in the ping.php script so that for example when Bob presses DND the script knows to tell Alice's phone to change the LED light associated with LINE1 on Bob's phone. You should add all your other phones into the script and the details about which lines and people are shown on each phone. This is done in the $devices array at the top of the file.
```

```
Connect to Alice's Yealink phone via its IP address. We're going to set a URL that the phone will hit when the DND function is turned on. This then tells our ping.php script that Alice has turned on DND mode, and the ping.php file can handle notifying the other phones. Replace zzz.zzz.zzz.zzz with your server's IP 

Navigate to features -> action URL and setup the Open DND Url to as follows : http://zzz.zzz.zzz.zzz/ping.php?key=gfsjh39esadsaFDsa&phone=bob&action=dnd&bool=1
```

```
Replace alice with the name of the phone as set in the ping.php file
```

```
Update the Close DND url to the same as above, except set bool=1 in the url to bool=0
```

```
Set your server's public IP in your phones configuration via the 'Remote Control' menu, see picture below.
```

![yealink remote server](https://github.com/TurnkeyTyranny/yealink-push-server/raw/master/jane.png)

```
Do these steps for all your other phones, give them an appropriate name instead of Bob or Alice. The names in ping.php and the urls will be case sensitive
```

```
Save the changes to your phone.
```


## Authors

* **Turnkey** - [TurnkeyTyranny](https://github.com/TurnkeyTyranny) - Email 394ad2f@gmail.com

## License

This project is licensed under the MIT License
