const express = require('express');
const cors = require('cors');
const http = require('http');
const { Server } = require('socket.io');
const { connectDB, sequelize } = require('./config/db.config');

// Routes
const authRoutes = require('./routes/auth.routes');
const driverRoutes = require('./routes/driver.routes');
const tripRoutes = require('./routes/trip.routes');
const childRoutes = require('./routes/child.routes');
const paymentRoutes = require('./routes/payment.routes');

const app = express();
const server = http.createServer(app);
const io = new Server(server, {
  cors: {
    origin: "*",
    methods: ["GET", "POST"]
  }
});

// Attach io to app to use in controllers
app.set('io', io);

// ================= MIDDLEWARE =================
app.use(cors());
app.use(express.json());

// ================= DATABASE =================
connectDB();
// Sync models
sequelize.sync();

// ================= ROUTES =================
app.use('/', authRoutes);
app.use('/driver', driverRoutes);
app.use('/trip', tripRoutes);
app.use('/children', childRoutes);
app.use('/payments', paymentRoutes);

// Health check
app.get('/', (req, res) => {
  res.send('SCB Backend is running 🚍');
});

// ================= SOCKET.IO =================
io.on('connection', (socket) => {
  console.log('A user connected:', socket.id);

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
