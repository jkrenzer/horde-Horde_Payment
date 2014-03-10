<?php
/**
 * Horde_Payment external Application interface.
 *
 * $Horde: incubator/Horde_Payment/lib/Application.php,v 1.1 2009/09/15 09:35:35 duck Exp $
 *
 * Copyright 2006-2009 The Horde Project (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (GPL). If you
 * did not receive this file, see http://www.fsf.org/copyleft/gpl.html.
 *
 * @author  Duck <duck@obala.net>
 * @package Horde_Payment
 */
class Horde_Payment_Application extends Horde_Registry_Application
{
    /**
     * Categories/Permissions
     */
    public function perms()
    {
        static $perms = array();

        if (!empty($perms)) {
            return $perms;
        }

        global $registry;

        $perms['tree']['Horde_Payment']['admin'] = false;
        $perms['title']['Horde_Payment:admin'] = _("Admin");

        $perms['title']['Horde_Payment:apps'] = _("Applications");
        foreach ($registry->listApps() as $app) {
            $perms['tree']['Horde_Payment']['apps'][$app] = false;
            $perms['title']['Horde_Payment:apps:' . $app] = $registry->get('name', $app) . ' (' . $app . ')';
        }

        return $perms;
    }
}