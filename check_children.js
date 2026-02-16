const mongoose = require('mongoose');
const Child = require('./models/Child');

async function check() {
    await mongoose.connect('mongodb://127.0.0.1:27017/scb_app');
    const children = await Child.find({});
    children.forEach(c => {
        console.log(`Child: ${c.name}, Home: (${c.homeLat}, ${c.homeLng}), School: (${c.schoolLat}, ${c.schoolLng}), Status: ${c.tripStatus}`);
    });
    process.exit();
}
check();
