const axios = require('axios');

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
  if (!start.lat || !start.lng || !end.lat || !end.lng) {
    console.error('Invalid coordinates for routing:', { start, end });
    return { points: [] };
  }

  try {
    return await calculateOsrmRoute(start, end);
  } catch (error) {
    console.error('Routing API Failure:', error.message);
    return await calculateOsrmRoute(start, end);
  }
};

exports.calculateRouteWithWaypoints = async (waypoints) => {
  if (!Array.isArray(waypoints) || waypoints.length < 2) {
    return { points: [] };
  }

  const normalized = waypoints
    .map((p) => ({
      lat: Number(p.lat),
      lng: Number(p.lng),
    }))
    .filter((p) => Number.isFinite(p.lat) && Number.isFinite(p.lng));

  if (normalized.length < 2) {
    return { points: [] };
  }

  const coordString = normalized.map((p) => `${p.lng},${p.lat}`).join(';');
  const url = `http://router.project-osrm.org/route/v1/driving/${coordString}?overview=full&geometries=geojson&alternatives=false&steps=false`;

  try {
    const res = await axios.get(url);
    if (res.data.routes && res.data.routes.length > 0) {
      const route = res.data.routes[0];
      const coords = route.geometry?.coordinates || [];
      const points = coords.map((c) => ({ lat: c[1], lng: c[0] }));
      return {
        points,
        distance: Number(route.distance || 0),
        duration: Number(route.duration || 0),
      };
    }
  } catch (e) {
    console.error('OSRM waypoint route failure:', e.message);
  }

  return {
    points: normalized,
    distance: 0,
    duration: 0,
  };
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
