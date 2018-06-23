<?php

	include "./../inc/cacheMgt.inc.php";
	include "./../inc/mysql.inc.php";
	
	//DB connect
	$link = mysqlConnect();
	header('Content-type: application/json');
	
	if (!$link) {
		echo "Error: Unable to connect to MySQL." . PHP_EOL;
		echo "Debugging errno: " . mysqli_connect_errno() . PHP_EOL;
		echo "Debugging error: " . mysqli_connect_error() . PHP_EOL;
		exit;
	}
		
	if(isset($_GET['v'])){
		$version = $_GET['v'];
		$version = strip_tags($version);
		$version = stripslashes($version);		
		$version = mysqli_real_escape_string($link, $version);
		$version = trim($version);
	}
	else {
		$version = "v1";
	}
	
	switch($version)
	{
		case "v2" : 
			$query = "
				SELECT 
					`stationCode`,
					`stationName`,
					`stationState`,
					`stationLat`,
					`stationLon`, 
					`stationKioskState`,	
					stationAdress, 	
					`stationNbEDock`, 
					`nbFreeDock`, 
					`nbFreeEDock`,	
					`stationNbBike`, 
					`stationNbEBike`,
					`stationNbBikeOverflow`,
					`stationNbEBikeOverflow`,
					stationInsertedInDb ,
					stationOperativeDate,
					stationLastChange,				   
					stationLastExit,
					stationSignaleHS,
					stationSignaleHSDate,
					`stationSignaledElectrified` as stationConnected, 
					`stationSignaledElectrifiedDate` as stationConnectionDate
				FROM `velib_station` 
				where 
					`stationNbEDock`+
					  `stationNbBike`+
					  `stationNbEBike`+
					  `nbFreeDock`+
					  `nbFreeEDock` > 0 
					and stationHidden = 0
			";
			break;
		case "v1" :
			$query = "
				SELECT 
					`velib_station`.`stationCode`,
					`stationName`,
					`stationState`,
					`stationLat`,
					`stationLon`, 
					`stationKioskState`,	
					stationAdress, 	
					`stationNbEDock`, 
					`nbFreeDock`, 
					`nbFreeEDock`,	
					`stationNbBike`, 
					`stationNbEBike`,
					`stationNbBikeOverflow`,
					`stationNbEBikeOverflow`,
					stationInsertedInDb ,
					stationOperativeDate,
					stationLastChange,
					timediff(now() , `stationLastChange`)             as timediff                           ,					
					hour(timediff(now() , `stationLastChange`))       as hourdiff ,
					stationLastExit,
				   timediff(sysdate() , `stationLastExit`)                        as lastExistDiff         ,					
					hour(timediff(now() , `stationLastExit`))                      as hourLastExistDiff     ,
					stationSignaleHS,
				   DATE_FORMAT(`stationSignaleHSDate`,'%d/%m/%Y') as stationSignaleHSDate                  ,
				   DATE_FORMAT(`stationSignaleHSDate`,'%H:%i')    as stationSignaleHSHeure                 ,
				   4 - stationSignaleHSCount              as nrRetraitDepuisSignalement						,
					`stationSignaledElectrified` as stationConnected, 
					`stationSignaledElectrifiedDate` as stationConnectionDate,
				   stationNbBike + stationNbEBike + stationNbBikeOverflow + stationNbEBikeOverflow  as station_nb_bike,
				   stationMinVelibNDay					
				FROM 
					`velib_station` LEFT JOIN 
					   (
								SELECT
										 `stationCode`,
										 min(`stationVelibMinVelib`) as stationMinVelibNDay
								FROM
										 `velib_station_min_velib`
								wHERE
										 1
										 and `stationStatDate` > DATE_ADD(NOW(), INTERVAL -3 DAY)
								group by
										 `stationCode`
					   ) as min_Velib 
					   ON min_Velib.`stationCode`  = `velib_station`.`stationCode` 
				where 
					`stationNbEDock`+
					  `stationNbBike`+
					  `stationNbEBike`+
					  `nbFreeDock`+
					  `nbFreeEDock` > 0 
					and stationHidden = 0
			";
			break;
		case "web" :
			$query = "
				SELECT 
					concat(`stationName`, '-', `velib_station`.`stationCode`) as station,					
					
					`stationLat`,
					`stationLon`, 
					`stationNbBike`, 					
					`stationNbBikeOverflow`,	
					`stationNbEBike`,
					`stationNbEBikeOverflow`, 
					`nbFreeEDock`,
					`nbFreeDock`,
					timediff(now() , `stationLastChange`)             as timediff                           ,					
					hour(timediff(now() , `stationLastChange`))       as hourdiff ,						
					`stationState`,
					stationAdress,						
				    timediff(sysdate() , `stationLastExit`)                        as lastExistDiff         ,					
					hour(timediff(now() , `stationLastExit`))                      as hourLastExistDiff     ,
					`velib_station`.`stationCode`,
					stationSignaleHS,
					DATE_FORMAT(`stationSignaleHSDate`,'%d/%m/%Y') as stationSignaleHSDate                  ,
					DATE_FORMAT(`stationSignaleHSDate`,'%H:%i')    as stationSignaleHSHeure                 ,	
					(case when stationSignaleHS = 1
						then 4 - stationSignaleHSCount
						else 0
					end) as nrRetraitDepuisSignalement,
					`stationSignaledElectrified` as stationConnected, 
					`stationSignaledElectrifiedDate` as stationConnectionDate,					
					stationNbBike + stationNbEBike + stationNbBikeOverflow + stationNbEBikeOverflow  as tot_station_nb_bike,
					(case when stationMinVelibNDay IS NULL
						then stationNbBike+ stationNbEBike
						else stationMinVelibNDay
					end) as stationMinVelibNDay
					,
					(case when stationVelibMinVelibOverflow IS NULL
						then stationNbBikeOverflow+ stationNbEBikeOverflow
						else stationVelibMinVelibOverflow
					end) as stationVelibMinVelibOverflow					
				FROM 
					`velib_station` LEFT JOIN 
					   (
								SELECT
										`stationCode`,
										MIN(`stationVelibMinVelib` - stationVelibMinVelibOverflow) AS stationMinVelibNDay,
										MIN( stationVelibMinVelibOverflow ) AS stationVelibMinVelibOverflow
								FROM
										 `velib_station_min_velib`
								wHERE
										 1
										 and `stationStatDate` > DATE_ADD(NOW(), INTERVAL -3 DAY)
								group by
										 `stationCode`
					   ) as min_Velib 
					   ON min_Velib.`stationCode`  = `velib_station`.`stationCode` 
				where 
					`stationNbEDock`+
					  `stationNbBike`+
					  `stationNbEBike`+
					  `nbFreeDock`+
					  `nbFreeEDock` > 0 
					and stationHidden = 0
			";
			break;
	}
	
	if(isCacheValid("stationList.api.".$version.".json"))
	{
		//load from cach
		getPageFromCache("stationList.api.".$version.".json");
	}
	else
		if (isset($query))
		{
			//echo $query;
			if ($result = mysqli_query($link, $query)) 
			{
				if (mysqli_num_rows($result)>0)
				{
					$n = 1;
					$size = mysqli_num_rows($result);
					$resultArray;

					while($row = mysqli_fetch_array($result, MYSQLI_ASSOC))
					{
						$resultArray[]=$row;
						$n = $n+1;			
					}	

					ob_start();
					echo json_encode($resultArray, JSON_HEX_APOS);
					$newPage = ob_get_contents();
					updatePageInCache("stationList.api.".$version.".json", $newPage);
					ob_end_clean(); 
					echo $newPage;					
				}
			}
		}
		else echo "empty";
	mysqlClose($link);

	
?>