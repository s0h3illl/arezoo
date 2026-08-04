# 02 — Users list

**What to build:** A screen where an admin can find any user on the platform. It lists everyone, survives the list growing, and can be searched by the two things an admin actually knows about a person — their name and their email.

**Blocked by:** 01 — Admin gate and the admin section

**Status:** ready-for-agent

- [ ] An admin sees every user, paginated, with a stable order
- [ ] Searching by name finds a user; searching by email finds a user; a partial match works for both
- [ ] Each row shows when the user registered and whether their email is verified
- [ ] Each row shows whether the user is blocked
- [ ] The list can be filtered to blocked users only
- [ ] A search that matches nothing shows an explained empty state, not a blank table
- [ ] A search term survives pagination — moving to page two does not silently drop the filter
- [ ] A non-admin cannot reach the screen (404), and a guest is redirected to login
- [ ] A browser smoke test visits the list and asserts no JavaScript errors
- [ ] Pint clean, larastan clean
