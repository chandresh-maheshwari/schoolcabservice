const express = require('express');
const cors = require('cors');
const http = require('http');
const path = require('path');
const { Server } = require('socket.io');
const { connectDB, sequelize } = require('./config/db.config');
require('dotenv').config();
// Routes
const authRoutes = require('./routes/auth.routes');
const driverRoutes = require('./routes/driver.routes');
const tripRoutes = require('./routes/trip.routes');
const childRoutes = require('./routes/child.routes');
const paymentRoutes = require('./routes/payment.routes');
const mobileEngagementRoutes = require('./routes/mobile-engagement.routes');

const app = express();
const server = http.createServer(app);
const io = new Server(server, {
  path: process.env.SOCKET_PATH || '/socket.io/',
  transports: ['websocket', 'polling'],
  pingInterval: 25000,
  pingTimeout: 60000,
  connectTimeout: 45000,
  allowEIO3: true,
  cors: {
    origin: "*",
    methods: ["GET", "POST"]
  }
});

// Attach io to app to use in controllers
app.set('io', io);

// ================= MIDDLEWARE =================
app.use(cors());
app.use(express.json({ limit: '10mb' }));
app.use('/uploads', express.static(path.join(__dirname, 'uploads')));

// ================= DATABASE =================
connectDB();
if (process.env.ENABLE_SEQUELIZE_SYNC === 'true') {
  sequelize.sync();
}

// ================= ROUTES =================
app.use('/', authRoutes);
app.use('/driver', driverRoutes);
app.use('/trip', tripRoutes);
app.use('/children', childRoutes);
app.use('/payments', paymentRoutes);
app.use('/mobile', mobileEngagementRoutes);

// Health check
app.get('/', (req, res) => {
  res.send('SCB Backend is running 🚍');
});

// ================= SOCKET.IO =================
io.on('connection', (socket) => {
  console.log('A user connected:', socket.id);

  socket.on('join_trip', (payload = {}) => {
    const role = payload.role || 'unknown';
    socket.join(`role:${role}`);

    const tripId = String(payload.tripId ?? '').trim();
    if (tripId) {
      socket.join(`trip:${tripId}`);
    }

    const parentId = String(payload.parentId ?? '').trim();
    if (parentId) {
      socket.join(`parent:${parentId}`);
    }

    const childId = String(payload.childId ?? '').trim();
    if (childId) {
      socket.join(`child:${childId}`);
    }

    console.log(
      `Socket joined rooms. socket=${socket.id} role=${role} tripId=${payload.tripId || '-'} parentId=${payload.parentId || '-'} childId=${payload.childId || '-'}`
    );
  });

  socket.on('error', (err) => {
    console.error(`Socket error (${socket.id}):`, err?.message || err);
  });

  socket.on('disconnect', () => {
    console.log('User disconnected');
  });
});

// ================= SERVER =================
const PORT = process.env.PORT || 3000;

server.listen(PORT, () => {
  console.log(`Server running on port ${PORT}`);
});

// Graceful error handling
server.on('error', (err) => {
  if (err.code === 'EADDRINUSE') {
    console.error(`❌ Port ${PORT} is already in use.`);
    console.error('➡ Kill the old process or change the port.');
    process.exit(1);
  } else {
    console.error(err);
  }
});
