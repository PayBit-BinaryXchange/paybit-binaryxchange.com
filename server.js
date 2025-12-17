//const { name } = require('ejs');
const express = require('express');
const bcrypt = require('bcrypt');
const path = require("path")
const initializePassport = require('./passport-config');
const passport = require('passport');
const session = require("express-session");
const flash = require("express-flash");
const mongoose = require("mongoose");
const User = require("./models/Users");
require('dotenv').config();
//const MongoStore = require("connect-mongo");

initializePassport(passport);
//const routes = require('routes')
const app = express();
app.use(express.json())
//app.use(app.router)
app.use(express.urlencoded({ extended: false }));
app.use(express.static(path.join(__dirname, "public")))
app.set('views', path.join(__dirname, 'views'));
app.set('view engine', 'ejs');
app.set("trust proxy", 1); // REQUIRED for Render


const port = process.env.PORT || 5000;

app.listen(port, () => {
  console.log(`Server running on port ${port}`);
});

mongoose.connect(process.env.MONGO_URI)
  .then(() => console.log("MongoDB connected"))
  .catch(err => console.error("MongoDB connection failed:", err));

  app.use(session({
      secret: process.env.SECRET_KEY || "render-session-secret",
      resave: false,
      saveUninitialized: false,
    cookie: {
    secure: true,
    sameSite: "none"
  }
    }));

    app.use(passport.initialize());
    app.use(passport.session());
    app.use(flash());



// Middleware

/* ================= REGISTER ================= */
app.post("/register", async (req, res) => {
  const {
    firstname,
    lastname,
    username,
    email,
    password,
    password2,
    mobile,
    referral,
    country,
    currency,
    account
  } = req.body;

  if (password !== password2) {
      req.flash("error", "Passwords do not match");
    return res.redirect("/Dashboard/register");
  }

  try {
    const hashedPassword = await bcrypt.hash(password, 10);

    await User.create({
      firstname,
      lastname,
      username,
      email,
      password: hashedPassword,
      mobile,
      referral,
      country,
      currency,
      account
    });
    req.flash("success", "Registered successfully. Please log in.");
    res.redirect("/Dashboard/login");
  } catch (err) {
  if (err.code === 11000) {
    req.flash("error", "Registration failed. Try again.");
    res.redirect("/Dashboard/register");  
  }
}
});

/* ================= LOGIN ================= */
app.post("/login",
  passport.authenticate("local", {
    successRedirect: "/Dashboard/account",
    failureRedirect: "/Dashboard/login",
    failureFlash: true
  })
);




/* ================= LOGOUT ================= */
app.get("/logout", (req, res, next) => {
  req.logout(err => {
    if (err) return next(err);
    res.redirect("/Dashboard/login");
  });
});

// HOME PAGE ROUTES
app.get('/test', (req, res) => {res.send('Render server is working!');
});

app.get('/', (req, res) => {res.render('index'); });

app.get('/home', (req, res) => {res.render('home'); });

app.get('/faq', (req, res) => { res.render('faq'); });

app.get('/terms', (req, res) => {res.render('terms'); });

app.get('/privacy', (req, res) => {res.render('privacy'); });

app.get('/contact', (req, res) => {res.render('contact'); });

app.get('/about', (req, res) => {res.render('about'); });

//DASHBOARD ROUTES
app.get('/Dashboard/login', (req, res) => {res.render('Dashboard/login');});

app.get('/Dashboard/register', (req, res) => {res.render('Dashboard/register'); });

app.get('/Dashboard/deposit', (req, res) => {res.render('Dashboard/deposit'); });

app.get('/Dashboard/home', (req, res) => {res.render('Dashboard/home'); });

app.get('/Dashboard/history', (req, res) => {res.render('Dashboard/history'); });

app.get('/Dashboard/index', (req, res) => {res.render('Dashboard/index'); });

app.get('/Dashboard/info', (req, res) => {res.render('Dashboard/info'); });

app.get('/Dashboard/logout', (req, res) => {res.render('Dashboard/logout'); });

app.post('/Dashboard/payment', (req, res) => {res.render('Dashboard/payment'); });

app.post('/Dashboard/deposit', (req, res) => {res.render('Dashboard/deposit'); });

app.get('/Dashboard/settings', (req, res) => {res.render('Dashboard/settings'); });

app.get('/Dashboard/signal', (req, res) => {res.render('Dashboard/signal'); });

app.get('/Dashboard/account', (req, res) => {res.render('Dashboard/account'); });

app.get('/Dashboard/forgot', (req, res) => {res.render('Dashboard/forgot'); });

app.get('/Dashboard/withdraw', (req, res) => {res.render('Dashboard/withdraw');});

app.get('/Dashboard/transaction', (req, res) => {res.render('Dashboard/transaction');});

app.get('/Dashboard/upgrade', (req, res) => {res.render('Dashboard/upgrade');});

//DEMO PHP ROUTES
app.get('/demo', (req, res) => {res.render('demo'); });

app.get('/arrow-call', (req, res) => {res.render('arrow-call'); });

app.get('/arrow-put', (req, res) => {res.render('arrow-put'); });

app.get('/clock-spinner', (req, res) => {res.render('clock-spinner'); });

//CAPTCHA ROUTE

const svgCaptcha = require("svg-captcha");

app.get("/captcha.php", (req, res) => {
  const captcha = svgCaptcha.create({
    size: 5,
    noise: 2,
    color: true,
    background: "#f4f4f4"
  });

  req.session.captcha = captcha.text;
  res.type("svg");
  res.send(captcha.data);
});



