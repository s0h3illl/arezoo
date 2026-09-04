# 06 — Ordering, filter, and the blocked badge

**Status:** ready-for-agent

## Parent

`.scratch/balance-and-withdrawals/spec.md`

## What to build

What turns the list into a queue. Undecided requests come first and oldest first among them, so page one is always the work and nobody waits longer than they should. A state filter lets paid and rejected history be reviewed apart from what is waiting — `Paid` and `Rejected` are permanent and accumulate forever, so without the ordering and the filter the table is eventually mostly history.

Note the neighbouring precedent and why it does not apply: the admin contributions list shipped with its status filter deliberately **removed**, because every contribution the app can produce is a successful payment and `pending` is a brief in-flight state. Withdrawal states are a real population, and this filter is wanted. Do not remove it by analogy.

The blocked badge is the third piece. An owner can be blocked after they request — often *because* of something discovered after they requested — and their request keeps sitting in the queue with a Sheba on it. The badge is information for the admin at the moment of decision. It changes nothing mechanically: blocking never touches money (`CONTEXT.md`), and no behaviour here is automatic.

## Acceptance criteria

- [ ] Undecided requests — `Requested` and `Accepted` — are listed before decided ones
- [ ] Undecided requests are ordered oldest first
- [ ] Decided requests follow, newest first
- [ ] The queue can be filtered by state, across all four states
- [ ] The filter's query-string plumbing survives pagination — filtering and paging compose
- [ ] An unfiltered queue is the default, and an unrecognised filter value does not error
- [ ] A row whose owner is blocked shows a badge saying so
- [ ] Blocking changes nothing else: a blocked owner's request can still be accepted, paid, or rejected, and no action is taken automatically
- [ ] A blocked owner's balance is unaffected by the block, and a test proves it
- [ ] A non-admin cannot reach the queue under any filter (404), and a guest gets the same 404
- [ ] A browser smoke test exercises the filter and asserts no JavaScript errors
- [ ] Pint clean, larastan clean

## Blocked by

- `03-see-and-pay-a-withdrawal.md`
