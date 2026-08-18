import os
import subprocess

def run_git(cmd):
    subprocess.run(cmd, shell=True, check=True)

groups = [
    {
        "name": "PIN & Role Authorization",
        "files": [
            "app/Http/Controllers/Api/AuthController.php",
            "app/Http/Controllers/Api/UserController.php",
            "resources/js/components/dialogs/AddEditRoleDialog.vue",
            "resources/js/pages/apps/pengaturan-pengguna.vue",
            "resources/js/views/apps/user/view/UserTabSecurity.vue",
            "routes/api.php",
            "resources/js/views/apps/roles/RoleCards.vue"
        ],
        "msg": "feat(auth): Enhance PIN authorization using dynamic Spatie queries & UI fixes"
    },
    {
        "name": "Audit & Closing Harian",
        "files": [
            "app/Http/Controllers/Api/CashReconciliationController.php",
            "app/Http/Controllers/Api/StockOpnameController.php",
            "resources/js/pages/audit/closing-harian.vue",
            "resources/js/pages/audit/stock-opname.vue"
        ],
        "msg": "feat(audit): Implement comprehensive daily closing and stock opname enhancements"
    },
    {
        "name": "Sales, POS & Transactions",
        "files": [
            "app/Http/Controllers/Api/SaleController.php",
            "resources/js/pages/pos/index.vue",
            "resources/js/pages/pos/ApprovalDialog.vue",
            "resources/js/pages/dashboards/penjualan.vue",
            "resources/js/pages/transaksi/index.vue"
        ],
        "msg": "feat(pos): Enhance POS approvals, sales tracking, and transaction layout"
    },
    {
        "name": "Receivables & Returns",
        "files": [
            "app/Http/Controllers/ReceivableController.php",
            "resources/js/pages/receivables/index.vue",
            "resources/js/pages/receivables/ReceivableDetailDrawer.vue",
            "resources/js/pages/retur/index.vue",
            "resources/js/pages/retur/CreateReturnDrawer.vue"
        ],
        "msg": "feat(finance): Update receivables detail drawer and product returns logic"
    },
    {
        "name": "User Profile & HRIS",
        "files": [
            "database/migrations/2026_08_16_140027_add_profile_fields_to_users_table.php",
            "resources/js/components/dialogs/UserInfoEditDialog.vue",
            "resources/js/views/apps/user/view/UserBioPanel.vue",
            "resources/js/views/apps/user/view/UserTabOverview.vue",
            "resources/js/views/pages/user-profile/connections/index.vue",
            "resources/js/views/pages/user-profile/profile/index.vue",
            "resources/js/views/pages/user-profile/projects/index.vue",
            "resources/js/views/pages/user-profile/team/index.vue",
            "resources/js/pages/apps/employees/index.vue"
        ],
        "msg": "feat(hris): Expand user profile fields, bio panel, and employee directory"
    },
    {
        "name": "Database Optimization",
        "files": [
            "database/migrations/2026_08_17_134300_add_performance_indexes.php"
        ],
        "msg": "chore(db): Add performance indexes for faster querying"
    },
    {
        "name": "Miscellaneous, Layout & Cleanup",
        "files": [
            "."
        ],
        "msg": "chore(ui): Miscellaneous UI improvements, layout tweaks, and general cleanup"
    }
]

for group in groups:
    for file in group["files"]:
        run_git(f'git add "{file}"')
    
    # Check if there's anything staged
    res = subprocess.run("git diff --cached --quiet", shell=True)
    if res.returncode != 0:
        run_git(f'git commit -m "{group["msg"]}"')
        print(f"Committed group: {group['name']}")

run_git("git push -f origin main")
print("Force pushed to origin main.")
