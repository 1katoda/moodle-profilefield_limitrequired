# profilefield_limitrequired

This is a text or password user profile field plugin for Moodle. It utilizes profile field parameters to set up the `required` flag based on user ID, user authentication type and course enrolments.

Limiting settings are:
- user ID
  - the profile field will display as required for the specified user IDs
- user authentication
  - the profile field will display as required for users with specified authentication types
- course enrolments
  - the profile field will display as required for users that are enroled in courses, specified by course IDs
- course enrolments
  - the profile field will display as required for users that are enroled in courses that reside under the categories, specified by course category IDs

If the profile field `required` flag is set, the limiting parameters are combined using `OR` logic, otherwise `AND`.
If the user IDs are specified, they take precedence over logic combinations.

