# profilefield_limitrequired

This is a text or password user profile field plugin for Moodle. It utilizes profile field parameters to set up the `required` flag based on user ID, user authentication type and course enrolments.

It can be used to force only a subset of Moodle users to fill out a required profile field and not to bother the others.

## Install

Plugin installation:
- download / clone the repository
- rename and move the repository to `user/profile/field/limitrequired`

## Usage

Limiting settings are:

- user ID
  - the profile field will display as required for the specified user IDs
- user authentication
  - the profile field will display as required for users with specified authentication types
- course enrolments
  - the profile field will display as required for users that are enroled in courses, specified by course IDs
- course enrolments under categories
  - the profile field will display as required for users that are enroled in courses that reside under the categories, specified by course category IDs

The limiting parameters are only applicable when the "Required" flag is set and are combined with "AND" logic.
If user IDs are specified, they take precedence over other logic combinations. The field will be required for all specified user IDs.

