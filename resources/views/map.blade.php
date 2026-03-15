<!DOCTYPE html>
<html>
<head>

<title>Pharmacies Map</title>

<link rel="stylesheet" href="https://unpkg.com/leaflet/dist/leaflet.css"/>

<style>
#map{
height:600px;
width:100%;
}
</style>

</head>
<body>

<h2>Pharmacies Map</h2>

<div id="map"></div>

<script src="https://unpkg.com/leaflet/dist/leaflet.js"></script>

<script>

var map = L.map('map').setView([33.5138,36.2765],13);

L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png',{
attribution:'© OpenStreetMap'
}).addTo(map);

</script>

</body>
</html>
