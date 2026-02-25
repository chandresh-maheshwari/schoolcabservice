const { sequelize } = require('./config/db.config');
const User = require('./models/User');
const Child = require('./models/Child');
const Trip = require('./models/Trip');
const Driver = require('./models/Driver');
const Payment = require('./models/Payment');
const bcrypt = require('bcryptjs');

async function seed() {
    try {
        await sequelize.authenticate();
        console.log('Connected to MySQL DB');

        // Ensure all models are registered before syncing
        void Trip;
        void Payment;

        // 1. Wipe everything and sync schema
        // force: true will drop tables if they exist and recreate them
        await sequelize.query('SET FOREIGN_KEY_CHECKS = 0');
        try {
            await sequelize.sync({ force: true });
        } finally {
            await sequelize.query('SET FOREIGN_KEY_CHECKS = 1');
        }
        console.log('Database synced (all tables dropped and recreated)');

        const hashedPassword = await bcrypt.hash('  ', 10);

        // 2. Create Users
        const driverUser = await User.create({
            email: 'driver@example.com',
            password: hashedPassword,
            role: 'driver'
        });

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

        const admin = await User.create({
            email: 'admin@scb.com',
            password: 'admin123',
            role: 'admin'
        });

        // 3. Create Driver Details
        await Driver.create({
            userId: driverUser.id,
            fullName: 'Rajesh Driver',
            phoneNumber: '9876543210',
            vehicleNumber: 'GJ-01-SCB-2026',
            vehicleModel: 'School Bus X',
            currentLat: 23.02431, // Shivranjani Flyover
            currentLng: 72.53016
        });

        // 4. Create Children
        // Memnagar (Aryan) -> Solaris School
        await Child.create({
            parentId: p1.id,
            name: 'Aryan',
            schoolName: 'Solaris School',
            className: 'Grade 5',
            homeLat: 23.0505,
            homeLng: 72.5290,
            schoolLat: 23.0645,
            schoolLng: 72.5248,
            secretPin: '1111',
            tripStatus: 'pending',
            subscriptionStatus: 'active'
        });

        // Science City (Kabir) -> Bhadaj
        await Child.create({
            parentId: p2.id,
            name: 'Kabir',
            schoolName: 'Science City High',
            className: 'Grade 8',
            homeLat: 23.0788,
            homeLng: 72.5050,
            schoolLat: 23.0950,
            schoolLng: 72.4950,
            secretPin: '2222',
            tripStatus: 'pending',
            subscriptionStatus: 'active'
        });

        console.log('--------------------------------------------------');
        console.log('SEED SUCCESSFUL!');
        console.log('--------------------------------------------------');
        console.log('DRIVER:  driver@example.com / password123');
        console.log('PARENT 1: parent1@example.com / password123 (Aryan)');
        console.log('PARENT 2: parent2@example.com / password123 (Kabir)');
        console.log('ADMIN:    admin@scb.com / admin123');
        console.log('--------------------------------------------------');

        process.exit(0);
    } catch (err) {
        console.error('Seed Error:', err);
        process.exit(1);
    }
}

seed();
