# Final-project demonstration

Open http://localhost:8000 and sign in with your existing administrator account. No existing passwords were changed, and no default admin or staff password was added to the real database.

## Prepare your demonstration

1. Open **Users → Create account**. Create two separate Staff accounts with passwords of 12–72 bytes. Choose **Admin** when demonstrating admin account creation.
2. Edit an account to demonstrate changing its name, email, role, password and active/disabled status. A blank reset-password field keeps the current password.
3. Keep your own admin account active. The app prevents deleting yourself, disabling yourself or changing your own admin role.

## Demonstrate the rubric

| Requirement | Demonstration |
| --- | --- |
| Login and logout | Sign in and use Logout for each role. |
| Profile and own password | Use **My profile & password** in either workspace. |
| Admin user management | Create, view, edit, disable/reactivate and delete a disposable staff account. User lists show email, role, date created and status; search and filter by role/status. |
| Dashboard totals | Show Total users, Total staff and Total records (products). Staff totals include disabled staff accounts. |
| Admin records | Open Products; create/edit any product and search/filter by status. The Created by column identifies ownership. |
| Staff records | Sign in as Staff A, open **Products**, create a product, edit it, and cancel a delete confirmation before confirming deletion of a disposable product. |
| Staff order management | Open **Orders** in the Staff workspace. Staff can view all customer orders and delivery details, filter by status, update fulfilment status, cancel unshipped orders and view receipts. Cancellation restores stock once; shipped/completed orders cannot be cancelled and status cannot move backwards. Admin-only pages remain blocked. |
| Staff product access | All staff can view every product. Staff can edit/delete only products they created. Other staff/admin products and existing products without a recorded creator are view-only; typing their edit/delete URLs returns 403. Admins can manage every product. Customers cannot use these management pages. |
| Admin-only restrictions | As staff, manually visit `/admin/`, `/admin/users/` or `/admin/products/`: access is denied. Staff menus contain no user management or admin dashboard. |
| Validation | Try a duplicate email, invalid email, short password, negative product price or stock. Invalid forms show errors and do not save. |
| Security | Passwords are hashed, changes use CSRF protection, role assignment is admin-only, and disabled accounts cannot log in or continue an existing session. Role changes revoke existing sessions. |
| Mobile | Open the admin or staff workspace on a narrow screen. Forms stack vertically; wide data tables scroll within their own container. |

Products referenced by orders are archived instead of deleted. Users with order history cannot be deleted; disable them instead. Deleting an unused staff account preserves its products; remaining staff can view them and admins can manage them.

Staff product editing/deletion follows the rubric's own-record requirement. Staff order management remains shared so staff can fulfil customer orders.

## Database and local setup

The additive migration is `DoctrineMigrations\Version20261110170000`. It adds account status and product creator attribution. Existing users remain active; existing products have no recorded creator and are accessible to staff and admins. It has already been applied to this computer's local database, preserving 3 users, 5 products and 4 orders.

A pre-update SQL backup is stored locally at `var/backups/finals-before-rubric-20261110.sql`. It contains private account data; keep it out of Git and public downloads. The migration intentionally refuses automatic rollback because removing status could reactivate disabled accounts and removing ownership would discard it. Restore a verified backup if rollback is necessary.

For another installation with the previous schema, back up its database before running:

```powershell
php bin/console doctrine:migrations:migrate --no-interaction
php bin/console cache:clear
```

The Docker source setup uses copied application files. After changing code, rebuilding the app image includes the changes. During this update, a fresh Docker build stalled while downloading packages; it was cancelled, and the updated source/config/templates/assets were copied to the existing app container. The current local app is running with these changes. A future rebuild should use the current source once downloads work.

## Verification

- Full PHPUnit suite: 62 tests, 541 assertions passing, including shared product visibility with ownership-protected writes, staff order management and customer permission/stock checks.
- Twig: 32 templates valid; YAML: 24 files valid; service container valid.
- MySQL entity mapping and database schema validated in the running app.
- Independent review found no issues in the scoped changes.
- Browser checks covered admin totals/account forms, staff product ownership display, delete confirmation (cancelled), a 375px staff form and the live app sign-in page.

For isolated browser practice, the existing QA preview helper can seed `var/qa/preview.sqlite`. Only that guarded preview database uses synthetic demo accounts. It does not change the real database. Tests use a different, guarded SQLite database.
