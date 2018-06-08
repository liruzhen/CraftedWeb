<?php
    /* ___           __ _           _ __    __     _     
      / __\ __ __ _ / _| |_ ___  __| / / /\ \ \___| |__
      / / | '__/ _` | |_| __/ _ \/ _` \ \/  \/ / _ \ '_ \
      / /__| | | (_| |  _| ||  __/ (_| |\  /\  /  __/ |_) |
      \____/_|  \__,_|_|  \__\___|\__,_| \/  \/ \___|_.__/

      -[ Created by �Nomsoft
      `-[ Original core by Anthony (Aka. CraftedDev)

      -CraftedWeb Generation II-
      __                           __ _
      /\ \ \___  _ __ ___  ___  ___  / _| |_
      /  \/ / _ \| '_ ` _ \/ __|/ _ \| |_| __|
      / /\  / (_) | | | | | \__ \ (_) |  _| |_
      \_\ \/ \___/|_| |_| |_|___/\___/|_|  \__|	- www.Nomsoftware.com -
      The policy of Nomsoftware states: Releasing our software
      or any other files are protected. You cannot re-release
      anywhere unless you were given permission.
      � Nomsoftware 'Nomsoft' 2011-2012. All rights reserved. */

    class Server
    {
        public function getRealmId($char_db)
        {
            global $Connect;
            $conn = $Connect->connectToDB();
            $Connect->selectDB('webdb', $conn);

            $get = mysqli_query($conn, "SELECT id FROM realms WHERE char_db='". mysqli_real_escape_string($conn, $char_db) ."';");
            $row = mysqli_fetch_assoc($get);

            return $row['id'];
        }

        public function getRealmName($char_db)
        {
            global $Connect;
            $conn = $Connect->connectToDB();
            $Connect->selectDB('webdb', $conn);

            $get = mysqli_query($conn, "SELECT name FROM realms WHERE char_db='". mysqli_real_escape_string($conn, $char_db) ."';");
            $row = mysqli_fetch_assoc($get);

            return $row['name'];
        }

        public function serverStatus($realm_id)
        {
            global $Connect;
            $conn = $Connect->connectToDB();

            $realmId = mysqli_real_escape_string($conn, $realm_id);
            //Get status
            $fp = fsockopen($GLOBALS['realms'][$realmId]['host'], $GLOBALS['realms'][$realmId]['port'], $errno, $errstr, 1);
            if (!$fp)
            {
                echo $status = "<h4 class='realm_status_title_offline'>" . $GLOBALS['realms'][$realmId]['name'] . " -  Offline</h4>";
            }
            else
            {
                echo $status = "<h4 class='realm_status_title_online'>" . $GLOBALS['realms'][$realmId]['name'] . " - Online</h4>";

                echo "<span class='realm_status_text'>";

                /* Players online bar */
                if ($GLOBALS['serverStatus']['factionBar'] == TRUE)
                {
                    $Connect->selectDB('chardb', $conn, $realmId);

                    $getChars     = mysqli_query($conn, "SELECT COUNT(online) AS online FROM characters WHERE online=1;");
                    $total_online = mysqli_fetch_assoc($getChars);

                    if ($total_online['online'] == 0)
                    {
                        $per_alliance = 50;
                        $per_horde    = 50;

                        $alliance['online'] = 0;
                        $horde['online']    = 0;
                    }
                    else
                    {
                        $getAlliance = mysqli_query($conn, "SELECT COUNT(online) AS online FROM characters WHERE online=1 AND race IN(3, 4, 7, 11, 1, 22);");
                        $alliance = mysqli_fetch_assoc($getAlliance);
                        if ($alliance['online'] == 0 || empty($alliance['online']))
                        {
                            $per_alliance = 0;
                        }
                        else
                        {
                            $per_alliance = ($alliance['online'] / $total_online['online']) * 100;
                        }

                        $getHorde = mysqli_query($conn, "SELECT COUNT(online) AS online FROM characters WHERE online=1 AND race IN(2, 5, 6, 8, 10, 9);");
                        $horde    = mysqli_fetch_assoc($getHorde);
                        if ($horde['online'] == 0 || empty($horde['online']))
                        {
                            $per_horde = 0;
                        }
                        else
                        {
                            $per_horde = (($horde['online'] / $total_online['online']) * 100);
                        }
                    }
                    /*if ($per_alliance + $per_horde > 100)
                    {
                        $per_horde = $per_horde - 1;
                    }*/
                    ?>
                    <div class='srv_status_po'>
                        <div class='srv_status_po_alliance' style="width: <?php echo $per_alliance; ?>%;"></div>
                        <div class='srv_status_po_horde' style="width: <?php echo $per_horde; ?>%;"></div>
                        <div class='srv_status_text'>
                            <b style="color:blue;">Alliance: <?php echo $alliance['online']; ?></b>
                            &nbsp;
                            <b style="color:red;">Horde: <?php echo $horde['online']; ?></b>
                        </div>
                    </div>
                    <?php
                }

                echo "<table width='100%'><tr>";
                //Get players online
                if ($GLOBALS['serverStatus']['playersOnline'] == TRUE)
                {
                    $Connect->selectDB('chardb', $conn, $realmId);
                    $getChars = mysqli_query($conn, "SELECT COUNT(online) AS online FROM characters WHERE online=1;");
                    $pOnline  = mysqli_fetch_assoc($getChars);
                    if ($pOnline['online'] > 1) 
                    {
                        echo "<td><b>". $pOnline['online'] ."</b> Players Online</td>";
                    }
                    elseif ($pOnline['online'] == 1)
                    {
                        echo "<td><b>". $pOnline['online'] ."</b> Player Online</td>";
                    }
                    else
                    {
                        echo "<td>No One Online</td>";
                    }
                    
                }

                //Get uptime
                if ($GLOBALS['serverStatus']['uptime'] == TRUE)
                {
                    $Connect->selectDB('logondb', $conn);
                    $getUp = mysqli_query($conn, "SELECT starttime FROM uptime WHERE realmid=". $realmId ." ORDER BY starttime DESC LIMIT 1;");
                    $row   = mysqli_fetch_assoc($getUp);

                    $time   = time();
                    $uptime = $time - $row['starttime'];

                    echo '
					<td>
					   <b>'. convTime($uptime) .'</b> uptime
					</td>
					</tr>';
                }
            }
            if ($GLOBALS['serverStatus']['nextArenaFlush'] == TRUE)
            {
                //Arena flush
                $Connect->selectDB('chardb', $conn, $realmId);
                
                $getFlush = mysqli_query($conn, "SELECT value FROM worldstates WHERE comment='NextArenaPointDistributionTime';");
                $row      = mysqli_fetch_assoc($getFlush);
                $flush    = date('d M H:i', $row['value']);

                echo '<tr>
			 	   <td>
				   	   Next arena flush: <b>' . $flush . '</b>
				   </td>';
            }
            echo '</tr>
		      </table>
			  </span>';
        }

    }

    $Server = new Server();
    