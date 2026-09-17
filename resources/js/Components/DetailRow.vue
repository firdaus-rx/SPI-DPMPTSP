<template>
    <div class="flex items-start justify-between py-2 border-b border-gray-50">
        <dt class="text-sm text-gray-500">{{ label }}</dt>
        <dd class="text-sm font-medium text-gray-900 text-right" :class="{ 'font-mono text-xs': mono, 'text-left w-full mt-1': full }">
            {{ value || '-' }}
        </dd>
    </div>
</template>

<script setup>
defineProps({
    label: String,
    value: [String, Number, null],
    mono: { type: Boolean, default: false },
    full: { type: Boolean, default: false },
});
</script>
