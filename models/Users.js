const mongoose = require("mongoose");

const UserSchema = new mongoose.Schema({
  firstname: String,
  lastname: String,
  username: { type: String, unique: true },
  email: { type: String, unique: true },
  password: String,
  mobile: String,
  referral: String,
  country: String,
  currency: String,
  account: [String], // multiple select
  createdAt: { type: Date, default: Date.now }
});

module.exports = mongoose.model("user", UserSchema);
