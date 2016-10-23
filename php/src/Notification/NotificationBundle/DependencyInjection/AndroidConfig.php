<?php

namespace Notification\NotificationBundle\DependencyInjection;

/**
 * This is the class that define the andriod configuration
 *
 * To learn more see {@link http://www.appgamekit.com/documentation/guides/push_notifications_android.htm}
 */

class AndroidConfig
{
    /**
     * {@inheritDoc}
     */
	// we have to change this(androidApiKey) according to the api key will be provided by Andriod team.
	#public $androidApiKey = "AIzaSyCk7y-1fMZAWGv6sJPs-414TLNSgclQmfw";
	
        /** token provided by android team**/
        public $androidApiKey = "AIzaSyD5n7NiZJCe-Gdtj4zaLc8iYI62jIMgAGY";
                                 
	public $badge = "0";
	public $sound = "default";
}
