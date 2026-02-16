const mongoose = require('mongoose');
const User = require('./models/User');
const Child = require('./models/Child');
const Trip = require('./models/Trip');
const Driver = require('./models/Driver');
const bcrypt = require('bcryptjs');

async function seed() {
    await mongoose.connect('mongodb://127.0.0.1:27017/scb_app');
    console.log('Connected to DB');

    // Clear existing
    await User.deleteMany({ email: { $in: ['parent1@example.com', 'parent2@example.com'] } });
    await Child.deleteMany({ name: { $in: ['Aryan', 'Kabir'] } });
    await Trip.deleteMany({});

    const hashedPassword = await bcrypt.hash('password123', 10);

    // Create Parents
    const p1 = await User.create({
        email: 'parent1@example.com',
        password: hashedPassword,
        role: 'parent'
    });
    const p2 = await User.create({
        email: 'parent2@example.com',
        password: hashedPassword,
        role: 'parent'
    });

    // Create Children
    await Child.create({
        parentId: p1._id,
        name: 'Aryan',
        schoolName: 'Solaris School',
        className: 'Grade 5',
        homeLat: 23.0500,
        homeLng: 72.5300,
        schoolLat: 23.0650,
        schoolLng: 72.5250,
        secretPin: '1111',
        tripStatus: 'pending'
    });

    await Child.create({
        parentId: p2._id,
        name: 'Kabir',
        schoolName: 'Science City High',
        className: 'Grade 8',
        homeLat: 23.0750,
        homeLng: 72.5100,
        schoolLat: 23.0900,
        schoolLng: 72.5000,
        secretPin: '2222',
        tripStatus: 'pending'
    });

    // Ensure Driver exists or update its current location to Shivranjani
    const driver = await Driver.findOne({});
    if (driver) {
        driver.currentLat = 23.0245;
        driver.currentLng = 72.5385;
        driver.stops = [];
        driver.currentRoute = null;
        await driver.save();
        console.log('Driver position reset to Shivranjani');
    }

    console.log('Seed completed successfully');
    console.log('Parent 1: parent1@example.com / password123 (Aryan)');
    console.log('Parent 2: parent2@example.com / password123 (Kabir)');

    mongoose.connection.close();
}

seed().catch(err => {
    console.error(err);
    process.exit(1);
});
