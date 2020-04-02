<?php
/* $Id$ */
/*
    Original status page code from: status_dhcp_leases.php
    Copyright (C) 2014 Tobias Schmid
    Edits to convert it to a widget: dhcp_leases.widget.php
	status_dhcp_leases.php
	Copyright (C) 2004-2009 Scott Ullrich
	All rights reserved.

	originally part of m0n0wall (http://m0n0.ch/wall)
	Copyright (C) 2003-2004 Manuel Kasper <mk@neon1.net>.
	All rights reserved.

	Redistribution and use in source and binary forms, with or without
	modification, are permitted provided that the following conditions are met:

	1. Redistributions of source code must retain the above copyright notice,
	   this list of conditions and the following disclaimer.

	2. Redistributions in binary form must reproduce the above copyright
	   notice, this list of conditions and the following disclaimer in the
	   documentation and/or other materials provided with the distribution.

	THIS SOFTWARE IS PROVIDED ``AS IS'' AND ANY EXPRESS OR IMPLIED WARRANTIES,
	INCLUDING, BUT NOT LIMITED TO, THE IMPLIED WARRANTIES OF MERCHANTABILITY
	AND FITNESS FOR A PARTICULAR PURPOSE ARE DISCLAIMED. IN NO EVENT SHALL THE
	AUTHOR BE LIABLE FOR ANY DIRECT, INDIRECT, INCIDENTAL, SPECIAL, EXEMPLARY,
	OR CONSEQUENTIAL DAMAGES (INCLUDING, BUT NOT LIMITED TO, PROCUREMENT OF
	SUBSTITUTE GOODS OR SERVICES; LOSS OF USE, DATA, OR PROFITS; OR BUSINESS
	INTERRUPTION) HOWEVER CAUSED AND ON ANY THEORY OF LIABILITY, WHETHER IN
	CONTRACT, STRICT LIABILITY, OR TORT (INCLUDING NEGLIGENCE OR OTHERWISE)
	ARISING IN ANY WAY OUT OF THE USE OF THIS SOFTWARE, EVEN IF ADVISED OF THE
	POSSIBILITY OF SUCH DAMAGE.
*/

/*
	pfSense_BUILDER_BINARIES:	/usr/bin/awk	/bin/cat	/usr/sbin/arp	/usr/bin/wc	/usr/bin/grep
	pfSense_MODULE:	dhcpserver
*/

##|+PRIV
##|*IDENT=page-status-dhcpleases
##|*NAME=Status: DHCP leases page
##|*DESCR=Allow access to the 'Status: DHCP leases' page.
##|*MATCH=status_dhcp_leases.php*
##|-PRIV


require_once("guiconfig.inc");
require_once("config.inc");
require_once("system.inc");
require_once("/usr/local/www/widgets/include/dhcp_leases.inc");

function adjust_gmt($dt) {
    global $config;
    $dhcpd = $config['dhcpd'];
    foreach ($dhcpd as $dhcpditem) {
        $dhcpleaseinlocaltime = $dhcpditem['dhcpleaseinlocaltime'];
        if ($dhcpleaseinlocaltime == "yes")
            break;
    }
    if ($dhcpleaseinlocaltime == "yes") {
        $ts = strtotime($dt . " GMT");
        return strftime("%Y/%m/%d %I:%M:%S%p", $ts);
    } else
        return $dt;
}

include("head.inc");

$leases = system_get_dhcpleases();

?>

<table class="table table-striped table-hover" id="dhcp_leases">
    <tr>
        <th><?=gettext("IP address"); ?></th>
        <th><?=gettext("Hostname"); ?></th>
        <th><?=gettext("Online"); ?></th>
        <th class="text-center"><?=gettext("Lease Type"); ?></th>
    </tr>
    <?php
    foreach ($leases as $data) {
        if (($data['act'] == "active") || ($data['act'] == "static")) {
            if ($data['online'] == "online") {
                $fspans = "<span class=\"text-success\">";
                $fspane = "</span>";
            } else {
                $fspans = "<span class=\"text-danger\">";
                $fspane = "</span>";
            }
            $lip = ip2ulong($data['ip']);
            if ($data['act'] == "static") {
                foreach ($config['dhcpd'] as $dhcpif => $dhcpifconf) {
                    if(is_array($dhcpifconf['staticmap'])) {
                        foreach ($dhcpifconf['staticmap'] as $staticent) {
                            if ($data['ip'] == $staticent['ipaddr']) {
                                $data['if'] = $dhcpif;
                                break;
                            }
                        }
                    }
                    /* exit as soon as we have an interface */
                    if ($data['if'] != "")
                        break;
                }
            } else {
                foreach ($config['dhcpd'] as $dhcpif => $dhcpifconf) {
                    if (!is_array($dhcpifconf['range']))
                        continue;
                    if (($lip >= ip2ulong($dhcpifconf['range']['from'])) && ($lip <= ip2ulong($dhcpifconf['range']['to']))) {
                        $data['if'] = $dhcpif;
                        break;
                    }
                }
            }
            echo "<tr>\n";
            echo "<td>{$data['ip']}</td>\n";
            echo "<td>"  . htmlentities($data['hostname']) . "</td>\n";
            echo "<td>{$fspans}{$data['online']}{$fspane}</td>\n";
            echo "<td class=\"text-center\">{$data['act']}</td>\n";
            echo "</tr>\n";
        }
    }

    ?>
</table>
<?php if($leases == 0): ?>
    <p><strong><?=gettext("No leases file found. Is the DHCP server active"); ?>?</strong></p>
<?php endif; ?>
