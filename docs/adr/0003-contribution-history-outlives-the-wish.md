# Contribution history outlives its participants

Wishes and users are both soft-deleted. Paid contributions are a permanent record of who gave what: deleting a wish must never destroy its contributions, and deleting a user account must never shrink a wish's received total. The deleted party is hidden, and UIs surface it as "deleted" (via `withTrashed()` on the relation) rather than showing its details.

Hard deletes (`forceDelete`) remain the deliberate full-purge path: FK cascades then remove dependent rows for real.
