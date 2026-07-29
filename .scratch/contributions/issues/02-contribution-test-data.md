# 02 — Contribution test data

**What to build:** The ability to conjure realistic contributions in tests and in a seeded database. The `Contribution` model already declares a factory that does not exist, so nothing can create one today. A developer running the seeders should end up with a wishlist where wishes have been partly and fully funded by a mix of contributors, so the app has something believable to show.

Every contribution is created alongside its own payment — never sharing one — matching the one-to-one relationship the models describe.

**Blocked by:** 01 — Prefactor: relocate enums into `App\Enums`

**Status:** done

- [x] A contribution factory exists, creating its own payment alongside each contribution
- [x] It offers readable states for a pending contribution and a paid one (a paid contribution's payment is verified and carries a reference; a pending one's is not)
- [x] It offers a way to set the contributor, the wish, and the visibility
- [x] Amounts generated are plausible Toman values, consistent with the wish factory's price range (see ADR-0002)
- [x] Seeding produces wishes at a mix of funding levels — some untouched, some partly funded, some over their price — with contributions spread across several contributors
- [x] `php artisan migrate:fresh --seed` completes without error
- [x] Pint clean, larastan clean
