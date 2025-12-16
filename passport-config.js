const LocalStrategy = require("passport-local").Strategy;
const bcrypt = require("bcrypt");
const User = require("./models/Users");

module.exports = function (passport) {
  passport.use(
    new LocalStrategy(
      { usernameField: "login" }, // single field
      async (login, password, done) => {
        try {
          const user = await User.findOne({
            $or: [
              { email: login },
              { username: login }
            ]
          });

          if (!user) {
            return done(null, false, { message: "User not found" });
          }

          const match = await bcrypt.compare(password, user.password);
          if (!match) {
            return done(null, false, { message: "Wrong password" });
          }

          return done(null, user);
        } catch (err) {
          return done(err);
        }
      }
    )
  );

  passport.serializeUser((user, done) => done(null, user.id));
  passport.deserializeUser(async (id, done) => {
    const user = await User.findById(id);
    done(null, user);
  });
};
