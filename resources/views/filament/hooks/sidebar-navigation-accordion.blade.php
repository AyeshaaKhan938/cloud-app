{{-- Accordion behavior: only one labeled navigation group expanded at a time (Filament sidebar store). --}}
<script>
    document.addEventListener('alpine:init', () => {
        const patch = () => {
            if (typeof Alpine === 'undefined' || !Alpine.store('sidebar')) {
                return;
            }

            const store = Alpine.store('sidebar');

            if (store.__vmsAccordionNavigationPatched) {
                return;
            }

            store.__vmsAccordionNavigationPatched = true;

            const originalToggle = store.toggleCollapsedGroup.bind(store);

            store.toggleCollapsedGroup = function (group) {
                const allLabels = Array.from(
                    document.querySelectorAll(
                        '.fi-main-sidebar .fi-sidebar-nav ul.fi-sidebar-nav-groups > li.fi-sidebar-group[data-group-label]',
                    ),
                )
                    .map((el) => el.getAttribute('data-group-label'))
                    .filter((label) => label !== null && label !== '');

                if (allLabels.length <= 1) {
                    originalToggle(group);

                    return;
                }

                const collapsed = Array.isArray(this.collapsedGroups) ? this.collapsedGroups : [];

                if (collapsed.includes(group)) {
                    this.collapsedGroups = allLabels.filter((g) => g !== group);
                } else {
                    originalToggle(group);
                }
            };
        };

        queueMicrotask(patch);

        document.addEventListener('livewire:navigated', () => {
            queueMicrotask(patch);
        });
    });
</script>
