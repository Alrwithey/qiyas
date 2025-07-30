<?php
session_start();
require_once __DIR__ . "/../config.php";
require_once __DIR__ . "/../functions.php";
require_once __DIR__ . "/../db.php";

require_login();
$settings = get_latest_settings($conn);
$programs = get_all_programs($conn);

$message = "";
$error = "";

// Handle Add/Update operations via POST
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Handle Add
    if (isset($_POST['add_program'])) {
        $program_name = sanitize_input($_POST['program_name']);
        if (!empty($program_name)) {
            if (add_program($conn, $program_name)) {
                $message = "تم إضافة البرنامج بنجاح.";
                $programs = get_all_programs($conn); // Refresh list
            } else { $error = "حدث خطأ أثناء إضافة البرنامج."; }
        } else { $error = "اسم البرنامج لا يمكن أن يكون فارغاً."; }
    }
    // Handle Update
    if (isset($_POST['update_program'])) {
        $id = intval($_POST['edit_id']);
        $program_name = sanitize_input($_POST['edit_name']);
        if (!empty($program_name) && $id > 0) {
            if (update_program($conn, $id, $program_name)) {
                $message = "تم تحديث البرنامج بنجاح.";
                $programs = get_all_programs($conn); // Refresh list
            } else { $error = "حدث خطأ أثناء تحديث البرنامج."; }
        } else { $error = "بيانات غير صالحة."; }
    }
}

// Handle Delete operation via GET
if (isset($_GET['delete'])) {
    $id = intval($_GET['delete']);
    if (delete_program($conn, $id)) {
        redirect('programs.php?message=deleted');
    } else {
        redirect('programs.php?error=delete_failed');
    }
}
if (isset($_GET['message']) && $_GET['message'] == 'deleted') {
    $message = "تم حذف البرنامج بنجاح.";
}
if (isset($_GET['error']) && $_GET['error'] == 'delete_failed') {
    $error = "حدث خطأ أثناء حذف البرنامج.";
}
?>

<!DOCTYPE html>
<html lang="ar" dir="rtl">
<?php echo get_admin_head($settings, "إدارة البرامج"); ?>
<style>
    .sortable-handle { cursor: move; color: #aaa; margin: 0 10px; }
    .sortable-ghost { background-color: #f0f8ff; border: 1px dashed #ccc; }
    #save-order-btn { display: none; margin-top: 20px; background-color: var(--success-color); }
</style>
<body>
    <button class="menu-toggle"><i class="fas fa-bars"></i></button>
    <div class="admin-wrapper">
        <div class="sidebar">
            <?php echo get_admin_header($settings); ?>
        </div>
        <div class="main-content">
            <h1>إدارة البرامج</h1>

            <?php if ($message): ?><div class="success"><?php echo $message; ?></div><?php endif; ?>
            <?php if ($error): ?><div class="error"><?php echo $error; ?></div><?php endif; ?>

            <div class="form-container">
                <h3>إضافة برنامج جديد</h3>
                <form action="programs.php" method="post" class="admin-form">
                    <div class="form-group">
                        <label for="program_name">اسم البرنامج</label>
                        <input type="text" id="program_name" name="program_name" required>
                    </div>
                    <button type="submit" name="add_program">إضافة البرنامج</button>
                </form>
            </div>

            <div class="table-container">
                <h3>قائمة البرامج (اسحب لترتيب)</h3>
                <div class="table-responsive">
                    <table class="table">
                        <thead>
                            <tr>
                                <th>ترتيب</th>
                                <th>اسم البرنامج</th>
                                <th>إجراءات</th>
                            </tr>
                        </thead>
                        <tbody id="sortable-programs">
                            <?php foreach ($programs as $program): ?>
                                <tr data-id="<?php echo $program['id']; ?>">
                                    <td><i class="fas fa-arrows-alt-v sortable-handle"></i></td>
                                    <td class="program-name"><?php echo htmlspecialchars($program['program_name']); ?></td>
                                    <td>
                                        <!-- THIS IS THE EDIT BUTTON - FIXED -->
                                        <button class="btn-edit edit-btn" data-id="<?php echo $program['id']; ?>" data-name="<?php echo htmlspecialchars($program['program_name']); ?>">تعديل</button>
                                        
                                        <a href="?delete=<?php echo $program['id']; ?>" class="btn-delete" onclick="return confirm('هل أنت متأكد من رغبتك في حذف هذا البرنامج؟');">حذف</a>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
                <button id="save-order-btn" class="btn-primary">حفظ الترتيب</button>
            </div>
        </div>
    </div>
    
    <!-- Include SortableJS library -->
    <script src="https://cdn.jsdelivr.net/npm/sortablejs@latest/Sortable.min.js"></script>
    <?php echo get_admin_footer(); ?>
    <script>
    document.addEventListener('DOMContentLoaded', function() {
        const sortableList = document.getElementById('sortable-programs');
        const saveBtn = document.getElementById('save-order-btn');

        // Sortable JS logic
        if (sortableList) {
            new Sortable(sortableList, {
                animation: 150,
                handle: '.sortable-handle',
                ghostClass: 'sortable-ghost',
                onEnd: function () {
                    saveBtn.style.display = 'inline-block';
                }
            });
        }

        // Save order logic
        if (saveBtn) {
            saveBtn.addEventListener('click', function() {
                const programIds = Array.from(sortableList.querySelectorAll('tr')).map(row => row.dataset.id);

                fetch('ajax_update_program_order.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ order: programIds })
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        Swal.fire('نجاح!', 'تم حفظ الترتيب الجديد بنجاح.', 'success').then(() => {
                            saveBtn.style.display = 'none';
                        });
                    } else {
                        Swal.fire('خطأ!', 'حدث خطأ أثناء حفظ الترتيب.', 'error');
                    }
                }).catch(error => Swal.fire('خطأ', 'فشل الاتصال بالخادم.', 'error'));
            });
        }

        // Edit button logic using SweetAlert2
                // ===== CORRECTED EDIT BUTTON LOGIC =====
        const editButtons = document.querySelectorAll('.edit-btn');
        editButtons.forEach(button => {
            button.addEventListener('click', function() {
                const programId = this.dataset.id;
                const programName = this.dataset.name;

                Swal.fire({
                    title: 'تعديل اسم البرنامج',
                    input: 'text',
                    inputValue: programName,
                    showCancelButton: true,
                    confirmButtonText: 'تحديث',
                    cancelButtonText: 'إلغاء',
                    inputValidator: (value) => {
                        if (!value) {
                            return 'اسم البرنامج لا يمكن أن يكون فارغاً!'
                        }
                    },
                    preConfirm: (newName) => {
                        // This is the simplified and correct way to submit a form.
                        // We create a form in memory and submit it.
                        const form = document.createElement('form');
                        form.method = 'POST';
                        form.action = 'programs.php'; // Submit to the same page

                        const idInput = document.createElement('input');
                        idInput.type = 'hidden';
                        idInput.name = 'edit_id';
                        idInput.value = programId;
                        form.appendChild(idInput);

                        const nameInput = document.createElement('input');
                        nameInput.type = 'hidden';
                        nameInput.name = 'edit_name';
                        nameInput.value = newName;
                        form.appendChild(nameInput);
                        
                        const actionInput = document.createElement('input');
                        actionInput.type = 'hidden';
                        actionInput.name = 'update_program'; // This tells PHP what to do
                        actionInput.value = '1';
                        form.appendChild(actionInput);

                        document.body.appendChild(form);
                        form.submit();
                    }
                });
            });
        });
    });
    </script>
</body>
</html>