const mongoose = require('mongoose');
const User = require('./models/User');
const Child = require('./models/Child');
const Trip = require('./models/Trip');
const Driver = require('./models/Driver');
const bcrypt = require('bcryptjs');

async function seed() {
    await mongoose.connect('mongodb://127.0.0.1:27017/scb_app');
    console.log('Connected to DB');

    // 1. Wipe everything
    await User.deleteMany({});
    await Child.deleteMany({});
    await Trip.deleteMany({});
    await Driver.deleteMany({});

    const hashedPassword = await bcrypt.hash('password123', 10);

    // 2. Create Users
    const driverUser = await User.create({
        email: 'driver@example.com',
        password: hashedPassword,
        role: 'driver'
    });

    const pMeet = await User.create({
        email: 'meet_parent@example.com',
        password: hashedPassword,
        role: 'parent'
    });

    const pFena = await User.create({
        email: 'fena_parent@example.com',
        password: hashedPassword,
        role: 'parent'
    });

    // 3. Create Driver Details
    await Driver.create({
        userId: driverUser._id,
        fullName: 'Rajesh Driver',
        phoneNumber: '9876543210',
        vehicleNumber: 'GJ-01-SCB-2026',
        vehicleModel: 'School Bus X',
        currentLat: 23.02431, // Shivranjani Flyover (STARTING POINT)
        currentLng: 72.53016
    });

    // 4. Create Children with Increased Distances for better tracking view

    // CHILD 1: Meet Patel (South-West journey first)
    await Child.create({
        parentId: pMeet._id,
        name: 'Meet Patel',
        schoolName: 'Zydus School',
        className: 'Grade 5',
        homeLat: 23.0063, // Prahlad Nagar (~3km from Start)
        homeLng: 72.5015,
        schoolLat: 23.0505, // Memnagar (~6km from Home)
        schoolLng: 72.5290,
        secretPin: '1904',
        tripStatus: 'pending',
        routeOrder: 1
    });

    // CHILD 2: Fena Patel (Science City journey after Meet is done)
    await Child.create({
        parentId: pFena._id,
        name: 'Fena Patel',
        schoolName: 'Nirma University',
        className: 'Engineering',
        homeLat: 23.0788, // Science City (~5km from Memnagar)
        homeLng: 72.5050,
        schoolLat: 23.1255, // Nirma University (~7km from Home)
        schoolLng: 72.5448,
        secretPin: '1305',
        tripStatus: 'pending',
        routeOrder: 2
    });

    console.log('--------------------------------------------------');
    console.log('DISTANCE UPDATED TEST DATA SEEDED!');
    console.log('--------------------------------------------------');
    console.log('DRIVER:  driver@example.com / password123');
    console.log('MEET: (Prahlad Nagar -> Memnagar)');
    console.log('FENA: (Science City -> Nirma Univ)');
    console.log('--------------------------------------------------');

    mongoose.connection.close();
}

seed().catch(err => {
    console.error(err);
    process.exit(1);
});
