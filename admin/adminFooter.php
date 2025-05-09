<script src="https://code.jquery.com/jquery-3.5.1.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/@popperjs/core@2.5.2/dist/umd/popper.min.js"></script>
<script src="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.min.js"></script>
<script>
    // Collapsible Employee menu
    const employeeBtn = document.getElementById('employeeBtn');
    const employeeSubMenu = document.getElementById('employeeSubMenu');
    let isOpen = false;
    employeeBtn.addEventListener('click', function() {
        isOpen = !isOpen;
        employeeSubMenu.classList.toggle('show', isOpen);
        // Rotate chevron
        const chevron = employeeBtn.querySelector('.fa-chevron-down');
        if (isOpen) {
            chevron.style.transform = 'rotate(180deg)';
        } else {
            chevron.style.transform = 'rotate(0deg)';
        }
    });

    // Show Create Account form on sidebar click
    const createAccountBtn = document.getElementById('createAccountBtn');
    const createAccountFormContainer = document.getElementById('createAccountFormContainer');
    const employeeListContainer = document.getElementById('employeeListContainer');
    const changePasswordFormContainer = document.getElementById('changePasswordFormContainer');
    const changePasswordBtn = document.getElementById('changePasswordBtn');
    const dashboardBtn = document.getElementById('dashboardBtn');
    const dashboardContent = document.getElementById('dashboardContent');
    
    createAccountBtn.addEventListener('click', function() {
        createAccountFormContainer.style.display = 'block';
        employeeListContainer.style.display = 'none';
        dashboardContent.style.display = 'none';
        changePasswordFormContainer.style.display = 'none';
    });

    changePasswordBtn.addEventListener('click', function() {
        changePasswordFormContainer.style.display = 'block';
        createAccountFormContainer.style.display = 'none';
        employeeListContainer.style.display = 'none';
        dashboardContent.style.display = 'none';
    });

    dashboardBtn.addEventListener('click', function() {
        dashboardContent.style.display = 'block';
        createAccountFormContainer.style.display = 'none';
        employeeListContainer.style.display = 'none';
        changePasswordFormContainer.style.display = 'none';
    });

    // Remove success message after 3 seconds
    function removeMessageAfterDelay(elementId) {
        const element = document.getElementById(elementId);
        if (element) {
            setTimeout(() => {
                element.style.display = 'none';
            }, 3000);
        }
    }

    if (<?php echo json_encode($create_account_success); ?>) {
        removeMessageAfterDelay('createAccountMsg');
        createAccountFormContainer.style.display = 'block';
        employeeListContainer.style.display = 'none';
    }

    if (<?php echo json_encode($change_password_success); ?>) {
        removeMessageAfterDelay('changePasswordMsg');
        changePasswordFormContainer.style.display = 'block';
        employeeListContainer.style.display = 'none';
    }

    // Show Employee List on button click
    employeeBtn.addEventListener('click', function() {
        employeeListContainer.style.display = 'block';
        createAccountFormContainer.style.display = 'none';
        changePasswordFormContainer.style.display = 'none';
        dashboardContent.style.display = 'none';
    });

    document.querySelectorAll('.pagination a').forEach(link => {
        link.addEventListener('click', function(event) {
            event.preventDefault();
            const page = this.getAttribute('data-page');
            if (page && page !== '#') {
                employeeListContainer.style.display = 'block';
                createAccountFormContainer.style.display = 'none';
                changePasswordFormContainer.style.display = 'none';
                dashboardContent.style.display = 'none';
                window.location.href = '?page=' + page;
            }
        });
    });

    document.querySelectorAll('.edit-change-password-user').forEach(button => {
        button.addEventListener('click', function() {
            const userId = this.getAttribute('data-user-id');
            const fullName = this.getAttribute('data-full-name');
            const email = this.getAttribute('data-email');
            const roleId = this.getAttribute('data-role-id');
            const departmentId = this.getAttribute('data-department-id');
            const jobRoleId = this.getAttribute('data-job-role-id');
            
            // Populate modal with user data
            $('#editUserId').val(userId);
            $('#editFullName').val(fullName);
            $('#editEmail').val(email);
            $('#editRole').val(roleId);
            $('#editDepartment').val(departmentId);
            $('#editJobRole').val(jobRoleId);
            
            $('#editChangePasswordModal').modal('show');
        });
    });

    // Filter and search functionality
    const searchInput = document.getElementById('search');
    const applySearchButton = document.getElementById('applySearch');
    const filterRoleSelect = document.getElementById('filterRole');
    const filterDepartmentSelect = document.getElementById('filterDepartment');
    const filterJobRoleSelect = document.getElementById('filterJobRole');
    const resetFiltersButton = document.getElementById('resetFilters');

    function applyFilters() {
        const search = searchInput.value;
        const filterRole = filterRoleSelect.value;
        const filterDepartment = filterDepartmentSelect.value;
        const filterJobRole = filterJobRoleSelect.value;

        const queryParams = new URLSearchParams(window.location.search);
        queryParams.set('search', search);
        queryParams.set('filterRole', filterRole);
        queryParams.set('filterDepartment', filterDepartment);
        queryParams.set('filterJobRole', filterJobRole);
        queryParams.set('page', 1); // Reset to first page on filter change

        window.location.search = queryParams.toString();
    }

    function resetFilters() {
        searchInput.value = '';
        filterRoleSelect.value = '';
        filterDepartmentSelect.value = '';
        filterJobRoleSelect.value = '';
        applyFilters();
    }

    applySearchButton.addEventListener('click', applyFilters);
    filterRoleSelect.addEventListener('change', applyFilters);
    filterDepartmentSelect.addEventListener('change', applyFilters);
    filterJobRoleSelect.addEventListener('change', applyFilters);
    resetFiltersButton.addEventListener('click', resetFilters);

    // Ensure only the relevant container is shown after actions
    if (<?php echo json_encode(isset($_POST['delete_user']) || isset($_POST['edit_user'])); ?>) {
        employeeListContainer.style.display = 'block';
        createAccountFormContainer.style.display = 'none';
        changePasswordFormContainer.style.display = 'none';
        dashboardContent.style.display = 'none';
    } else if (<?php echo json_encode(isset($_POST['create_account'])); ?>) {
        createAccountFormContainer.style.display = 'block';
        employeeListContainer.style.display = 'none';
        changePasswordFormContainer.style.display = 'none';
        dashboardContent.style.display = 'none';
    } else if (<?php echo json_encode(isset($_POST['change_password'])); ?>) {
        changePasswordFormContainer.style.display = 'block';
        createAccountFormContainer.style.display = 'none';
        employeeListContainer.style.display = 'none';
        dashboardContent.style.display = 'none';
    } else if (window.location.search.includes('search') || window.location.search.includes('filterRole') || window.location.search.includes('filterDepartment') || window.location.search.includes('filterJobRole')) {
        // Show employee list if any filter or search is applied
        employeeListContainer.style.display = 'block';
        createAccountFormContainer.style.display = 'none';
        changePasswordFormContainer.style.display = 'none';
        dashboardContent.style.display = 'none';
    } else {
        // Default to showing the dashboard if no specific action is detected
        dashboardContent.style.display = 'block';
        createAccountFormContainer.style.display = 'none';
        employeeListContainer.style.display = 'none';
        changePasswordFormContainer.style.display = 'none';
    }

    window.addEventListener('DOMContentLoaded', () => {
    const urlParams = new URLSearchParams(window.location.search);
    const show = urlParams.get('show');

    const createAccountFormContainer = document.getElementById('createAccountFormContainer');
    const changePasswordFormContainer = document.getElementById('changePasswordFormContainer');
    const employeeListContainer = document.getElementById('employeeListContainer');
    const dashboardContent = document.getElementById('dashboardContent'); // optional if exists

    // Hide/show sections based on `show` parameter
    if (show === 'createAccount') {
        createAccountFormContainer.style.display = 'block';
        changePasswordFormContainer.style.display = 'none';
        employeeListContainer.style.display = 'none';
        if (dashboardContent) dashboardContent.style.display = 'none';
    } else if (show === 'changePassword') {
        createAccountFormContainer.style.display = 'none';
        changePasswordFormContainer.style.display = 'block';
        employeeListContainer.style.display = 'none';
        if (dashboardContent) dashboardContent.style.display = 'none';
    } else {
        createAccountFormContainer.style.display = 'none';
        changePasswordFormContainer.style.display = 'none';
        employeeListContainer.style.display = 'block'; // default to employee list
        if (dashboardContent) dashboardContent.style.display = 'none';
    }

    // Pagination: override link behavior to stay on correct section
    document.querySelectorAll('.pagination a').forEach(link => {
        link.addEventListener('click', function(event) {
            event.preventDefault();
            const page = this.getAttribute('data-page');
            if (page && page !== '#') {
                const newParams = new URLSearchParams(window.location.search);
                newParams.set('page', page);
                newParams.set('show', 'employeeList'); // stay in employee list
                window.location.search = newParams.toString();
            }
        });
    });
});
</script>
</body>
</html>