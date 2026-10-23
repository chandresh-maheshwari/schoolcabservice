        // All Schools is a shortcut for selecting the schools currently listed.
        (function () {
            const schoolSelect = document.getElementById('school_id');
            if (!schoolSelect || !schoolSelect.multiple) return;

            function expandAllSchools() {
                if (!Array.from(schoolSelect.selectedOptions).some(option => option.value === '__all_schools__')) return;
                Array.from(schoolSelect.options).forEach(option => {
                    option.selected = option.value !== '' && option.value !== '__all_schools__';
                });
                $(schoolSelect).trigger('change');
            }

            $(schoolSelect).on('change.packageSchools', expandAllSchools);
            expandAllSchools();
        })();
