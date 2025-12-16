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

initializePassport(passport);
//const routes = require('routes')
const app = express();
app.use(express.json())
//app.use(app.router)
app.use(express.urlencoded({ extended: false }));
app.use(express.static(path.join(__dirname, "public")))
app.set('views', path.join(__dirname, 'views'));
app.set('view engine', 'ejs');
app.use(
  session({
    secret: "secretkey",
    resave: false,
    saveUninitialized: false
  })
);
app.use(passport.initialize());
app.use(passport.session());
app.use(flash());
//app.set('view engine', 'html');


mongoose.connect(process.env.MONGO_URI, { 
  useNewUrlParser: true, 
  useUnifiedTopology: true 
})
.then(() => console.log("MongoDB connected"))
.catch(err => console.error(err));


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
    return res.send("Passwords do not match");
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

    res.redirect("/Dashboard/login"); // your login page
  } catch (err) {
  if (err.code === 11000) {
    res.send("User already exists");
  } else {
    res.send("Error occurred during registration");
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
app.get("/logout", (req, res) => {
  req.logout(() => {
    res.redirect("/Dashboard/login");
  });
});

// HOME PAGE ROUTES
app.get('/index', (req, res) => {res.render('index'); });

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

app.get('/Dashboard/signal', (req, res) => {res.render('Dashboard//signal'); });

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




app.listen(5000, () => {console.log('Server is running on http://localhost:5000');});