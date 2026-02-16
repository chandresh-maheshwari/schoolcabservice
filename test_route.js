const { calculateRoute } = require('./services/route.service');

async function test() {
    const start = { lat: 23.0225, lng: 72.5714 };
    const end = { lat: 23.0400, lng: 72.5290 };
    try {
        const route = await calculateRoute(start, end);
        console.log('Route Points:', route.points.length);
        if (route.points.length > 0) {
            console.log('First point:', route.points[0]);
        }
    } catch (e) {
        console.error('Test Failed:', e);
    }
}
test();
