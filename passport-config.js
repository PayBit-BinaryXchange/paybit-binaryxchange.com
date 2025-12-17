const LocalStrategy = require("passport-local").Strategy;
const bcrypt = require("bcrypt");
const Users = require("./models/Users");

module.exports = function (passport) {
  passport.use(
    new LocalStrategy(
      { usernameField: "login" }, // single field
      async (login, password, done) => {
        try {
          const users = await Users.findOne({
            $or: [
              { email: login },
              { username: login }
            ]
          });

          if (!users) {
            return done(null, false, { message: "User not found" });
          }

          const match = await bcrypt.compare(password, users.password);
          if (!match) {
            return done(null, false, { message: "Wrong password" });
          }

          return done(null, users);
        } catch (err) {
          return done(err);
        }
      }
    )
  );

passport.serializeUser((users, done) => {
  done(null, users.id);
});

passport.deserializeUser(async (id, done) => {
  try {
    const user = await Users.findById(id);
    done(null, user);
  } catch (err) {
    done(err);
  }
});

};