const axios = require('axios');
const { distanceInMeters } = require('../utils/distance');

exports.buildStopsNearestFirst = (children, lat, lng, tripType = 'morning') => {
  const isMorning = tripType === 'morning';
  const orderedStops = [];

  console.log(`[ROUTE] Generating Sequence for ${children.length} children using Priority Order.`);

  for (const child of children) {
    const pLat = parseFloat(isMorning ? child.homeLat : child.schoolLat);
    const pLng = parseFloat(isMorning ? child.homeLng : child.schoolLng);
    const dLat = parseFloat(isMorning ? child.schoolLat : child.homeLat);
    const dLng = parseFloat(isMorning ? child.schoolLng : child.homeLng);

    // Add Pickup stop
    orderedStops.push({
      childId: child._id,
      name: child.name,
      type: 'pickup',
      lat: pLat,
      lng: pLng,
      status: 'pending'
    });

    // Add Drop-off stop
    orderedStops.push({
      childId: child._id,
      name: child.name,
      type: 'dropoff',
      lat: dLat,
      lng: dLng,
      status: 'pending'
    });

    console.log(`[SEQUENCE] Added Child: ${child.name} (Pick -> Drop)`);
  }

  return orderedStops;
};

exports.calculateRoute = async (start, end) => {
  const apiKey = 'AIzaSyA0VP54BjAwzI_n67R-8CqUOfnlndQJR6Y';

  if (!start.lat || !start.lng || !end.lat || !end.lng) {
    console.error('Invalid coordinates for routing:', { start, end });
    return { points: [] };
  }

  const url = `https://maps.googleapis.com/maps/api/directions/json?origin=${start.lat},${start.lng}&destination=${end.lat},${end.lng}&key=${apiKey}`;

  console.log(`Calculating route from (${start.lat},${start.lng}) to (${end.lat},${end.lng})`);

  try {
    const res = await axios.get(url);

    if (res.data.status !== 'OK') {
      console.error(`Google Directions Error: ${res.data.status}`);
      // Fallback to OSRM
      return await calculateOsrmRoute(start, end);
    }

    if (!res.data.routes || res.data.routes.length === 0) {
      return await calculateOsrmRoute(start, end);
    }

    const encoded = res.data.routes[0].overview_polyline.points;
    const points = decodePolyline(encoded);
    return { points };
  } catch (error) {
    console.error('Routing API Failure:', error.message);
    return await calculateOsrmRoute(start, end);
  }
};

async function calculateOsrmRoute(start, end) {
  try {
    const url = `http://router.project-osrm.org/route/v1/driving/${start.lng},${start.lat};${end.lng},${end.lat}?overview=full&geometries=geojson`;
    const res = await axios.get(url);
    if (res.data.routes && res.data.routes.length > 0) {
      const coords = res.data.routes[0].geometry.coordinates;
      const points = coords.map(c => ({ lat: c[1], lng: c[0] }));
      console.log(`OSRM Route found with ${points.length} points.`);
      return { points };
    }
  } catch (e) {
    console.error('OSRM Failure:', e.message);
  }
  return { points: [{ lat: start.lat, lng: start.lng }, { lat: end.lat, lng: end.lng }] };
}

function decodePolyline(encoded) {
  let points = [];
  let index = 0, len = encoded.length;
  let lat = 0, lng = 0;

  while (index < len) {
    let b, shift = 0, result = 0;
    do {
      b = encoded.charCodeAt(index++) - 63;
      result |= (b & 0x1f) << shift;
      shift += 5;
    } while (b >= 0x20);
    let dlat = ((result & 1) ? ~(result >> 1) : (result >> 1));
    lat += dlat;

    shift = 0;
    result = 0;
    do {
      b = encoded.charCodeAt(index++) - 63;
      result |= (b & 0x1f) << shift;
      shift += 5;
    } while (b >= 0x20);
    let dlng = ((result & 1) ? ~(result >> 1) : (result >> 1));
    lng += dlng;

    points.push({ lat: lat / 1e5, lng: lng / 1e5 });
  }
  return points;
}
