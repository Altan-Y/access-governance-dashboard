# Production concept vs. public demo

The work that inspired this portfolio project involved an internal PHP application for visualizing responsibilities around job roles and access groups.

## Production concept

The internal concept included:

- authentication through LDAPS and Active Directory;
- a connection to a remote MySQL-compatible database;
- server-side SQL queries for access groups, job roles, owners and approvers;
- permission checks before changing a description field;
- an interface for search, filtering and maintaining notes or descriptions.

## Public demo

This repository is a separate, independently written implementation. To keep it safe and easy to run, it replaces production dependencies with:

- fictional local users instead of an Active Directory connection;
- SQLite or JSON instead of an internal database server;
- synthetic groups, roles and identities;
- neutral branding and no internal links or content.

The demo therefore reflects the problem, workflow and several technical patterns, but it is not a copy of a production system and does not claim to contain a live LDAP integration.
