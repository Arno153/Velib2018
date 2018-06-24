<!DOCTYPE html> 
<html lang="fr"> 
<head>
  	<!-- Global site tag (gtag.js) - Google Analytics -->
	<script async src="https://www.googletagmanager.com/gtag/js?id=UA-113973828-2"></script>
	<script>
	  window.dataLayer = window.dataLayer || [];
	  function gtag(){dataLayer.push(arguments);}
	  gtag('js', new Date());

	  gtag('config', 'UA-113973828-2');
	</script>

	<title>Velib Paris - Carte officieuse des stations et velib disponibles</title>
	<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
	<meta name="description" content="Carte officieuse des stations du nouveau velib 2018: stations qui fonctionnent ou peut être pas, nombre de velos et VAE disponibles..." />
	<meta name="keywords" content="velib, velib 2018, velib2018, velib 2, cartes, geolocalisation, gps, autour de moi, station, vélo, paris, fonctionnent, disponibles, HS, en panne" />
	<meta name="viewport" content="initial-scale=1.0, width=device-width" />
	<meta name="robots" content="index, follow">
	<link rel="canonical" href="https://velib.philibert.info/carte-des-stations.php" />
	
	<link rel="apple-touch-icon" sizes="180x180" href="/apple-touch-icon.png">
	<link rel="icon" type="image/png" sizes="32x32" href="/favicon-32x32.png">
	<link rel="icon" type="image/png" sizes="16x16" href="/favicon-16x16.png">
	<link rel="manifest" href="/site.webmanifest">
	<link rel="mask-icon" href="/safari-pinned-tab.svg" color="#5bbad5">
	<meta name="msapplication-TileColor" content="#00a300">
	<meta name="msapplication-TileImage" content="/mstile-144x144.png">
	<meta name="theme-color" content="#ffffff">
	<link rel="stylesheet" media="all" href="./css/joujouVelib.css?<?php echo filemtime('./css/joujouVelib.css');?>">
	<script src="./inc/mapLeaflet.js?<?php echo filemtime('./inc/mapLeaflet.js');?>" type="text/javascript"></script>	
	
	
	<!-- Base MAP -->
	<link rel="stylesheet" href="https://unpkg.com/leaflet@1.3.1/dist/leaflet.css"
	   integrity="sha512-Rksm5RenBEKSKFjgI3a41vrjkw4EVPlJ3+OiI65vTjIdo9brlAacEuKOiQ5OFh7cOI1bkDwLqdLw3Zg0cRJAAQ=="
	   crossorigin=""/>
	<!-- Make sure you put this AFTER Leaflet's CSS -->
	<script src="https://unpkg.com/leaflet@1.3.1/dist/leaflet.js"
	   integrity="sha512-/Nsx9X4HebavoBvEBuyp3I7od5tA0UzAxs+j83KgC8PU0kgB4XiK4Lfe4y4cgBtaRJQEIFCW+oC506aPT2L1zw=="
	   crossorigin=""></script>
	<!-- Base MAP END-->
	
	<!-- LOCATION CONTROLE -->   
	<link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/font-awesome/4.7.0/css/font-awesome.min.css">
	<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/leaflet.locatecontrol/dist/L.Control.Locate.min.css" />

	<script src="https://cdn.jsdelivr.net/npm/leaflet.locatecontrol/dist/L.Control.Locate.min.js" charset="utf-8"></script>
	<!-- LOCATION CONTROLE END -->  

	<!-- geocoding  -->
	<link rel="stylesheet" href="https://unpkg.com/leaflet-control-geocoder/dist/Control.Geocoder.css" />
	<script src="https://unpkg.com/leaflet-control-geocoder/dist/Control.Geocoder.js"></script>
	<!-- geocoding  END-->
	
	<!-- full screen-->
	<script src='https://api.mapbox.com/mapbox.js/plugins/leaflet-fullscreen/v1.0.1/Leaflet.fullscreen.min.js'></script>
	<link href='https://api.mapbox.com/mapbox.js/plugins/leaflet-fullscreen/v1.0.1/leaflet.fullscreen.css' rel='stylesheet' />
	<!-- full screen END-->
	
	<!-- custop controle -- refresh  -->
	<script src="./inc/Leaflet.Control.Custom.js"></script>
	<!-- custop controle -- refresh  END -->
  </head>
  <body>
	<?php	
	include "./inc/mysql.inc.php";

	$lofFile='./.maintenance';
	if(file_exists ($lofFile) )
	{
		echo 
			"
			<div class='maintenance'>
				<!-- !!! Mode maintenance actif !!! -->
					Les données diffusées actuellement par velib métropole présentent des variations cycliques du nombre de vélo en station probablement non representatives des mouvements réels.
			</div>	
			";
	}
		
	?>
	<nav class="navbar bg-light"><b>
      <a class="nav-link" href="./">Accueil</a>
	  <a class="nav-link" href="javascript:refresh();">Carte</a>
	  <a class="nav-link" href="./liste-des-stations.php">Liste des stations</a>
    </b>
	</nav>
    <div id="mapid"></div>
    <script type="text/javascript">		
		var locations;
		var marker, i, iconurl;
		var markers = [];
		var HS;	
		
		var zoomp = getUrlParam('zoom');
		if(zoomp == undefined){
				var zoomp = 13;
		} 
		
		var latp = getUrlParam('lat');
		if(latp == undefined){
				var latp = 48.86;
		}
		
		var lonp  = getUrlParam('lon');
		if(lonp == undefined){
				var lonp = 2.34;
		}
		
		
		// initiate leaflet map
		var mymap = L.map('mapid', {
			center: [latp, lonp],
			zoom: zoomp,
			zoomControl: false
		})
		// add zoomControl
		L.control.zoom({ position: 'topright' }).addTo(mymap);
		
		// create a cutom control to refresh data (display only in fullscreen mode)		
		var cc = L.control.custom({
							position: 'topleft',
							title: 'Rafraichir',
							content : '<a class="leaflet-bar-part leaflet-bar-part-single" id="ReloadData">'+
									  '    <i class="fa fa-refresh"></i> '+
									  '</a>',
							classes : 'leaflet-control-locate leaflet-bar leaflet-control',
							style   :
							{
								padding: '0px',
							},
							events:
								{
									click: function(data)
									{
										refresh();									
									},
								}
						})
						.addTo(mymap);
						
		
		
		// add full screen control
		mymap.addControl(new L.Control.Fullscreen());
		
		// `fullscreenchange` Event that's fired when entering or exiting fullscreen.
		mymap.on('fullscreenchange', function () {
			if (mymap.isFullscreen()) {
				refresh();
			} else {
				refresh();
			}
		});
		
		// set map area limits
		var southWest = L.latLng(48.74, 2.14),
		northEast = L.latLng( 48.98, 2.55),
		mybounds = L.latLngBounds(southWest, northEast);		
		mymap.setMaxBounds(mybounds);
		mymap.options.minZoom = 11;
		mymap.options.maxBoundsViscosity = 1.0;

		//Load tiles
		L.tileLayer('https://velib.philibert.info/tiles/hot/{z}/{x}/{y}.png', {
			    attribution: 'donn&eacute;es &copy; <a href="//osm.org/copyright">OpenStreetMap</a>/ODbL - Tiles courtesy of <a href="https://hot.openstreetmap.org/">Humanitarian OpenStreetMap Team</a>',
		}).addTo(mymap);	

		
		//add geolocation control
		var lc = L.control.locate({
			position: 'topleft',
			strings: {
				title: "Geolocalisation!"
			},
			locateOptions: {
				maxZoom: 16,
				enableHighAccuracy: true
				
			}
		}).addTo(mymap);
		
		//load stations to the map
		getStations();
		
		// add adress search control		
		L.Control.geocoder().addTo(mymap);

    </script>
	
	<div class="disclaimer">
		Stations Velib suivant les derniers mouvements : <img src="./images/marker_green0.png" alt="Vert" width="12"><1h
		<<img src="./images/marker_yellow0.png" alt="Jaune" width="12"><3h
		<<img src="./images/marker_orange0.png" alt="Orange" width="12"><12h
		<<img src="./images/marker_red0.png" alt="Rouge" width="12"><24h
		<<img src="./images/marker_purple0.png" alt="Violet" width="12">  --
		<img src="./images/marker_grey0.png" alt="Gris" width="12"> : En travaux / Fermée -- NB: Malus pour les stations sans retraits
		<br>Données communautaire: <img src="./images/marker_greenx10.png" alt="Croix" width="12"> Signalée HS
		*** Alimentation Electrique: 
					<img src="./images/marker_p_green0.png" alt="Enedis Powered" width="12"> Enedis 
					- <img src="./images/marker_green0.png" alt="batterie" width="12"> Sur batterie
					- <img src="./images/marker_u_green0.png" alt="inconnue" width="12"> Inconnue
		
		

		<br>L'absence de mouvement ne présume pas du dysfonctionnement d'une station, l'inverse est également vrai... 
		<b>Tous les symboles de la V-BOX <a href="http://blog.velib-metropole.fr/wp-content/uploads/2018/02/PICTOS_LISTE_VELIB-.pdf" target="_blank">chez velib metropole</a> </b>
		<br><b>Ce site n'est pas un site officiel de vélib.</b> Les données utilisées proviennent de <a href="www.velib-metropole.fr">www.velib-metropole.fr</a> et appartiennent à leur propriétaire.
		<br>Contact: <a href="https://twitter.com/arno152153"><img border="0" alt="Twitter" src="https://abs.twimg.com/favicons/favicon.ico" width="15px" height="15px"></a>
		<?php 
			$link = mysqlConnect();
			echo " (Dernière collecte: ".getLastUpdate($link).")</h3>";		
			mysqlClose($link);	
		?>		
	</div>	
	
	<div id="mypub">
		<iframe id="gads" src="./inc/ads.inc.html" width="100%" height="600px" />
	</div>
	
  </body>
</html>