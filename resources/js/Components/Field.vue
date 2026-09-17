<template>
    <div :class="`col-span-3 md:col-span-${span}`">
        <label class="label">{{ label }}<span v-if="required" class="text-red-500 ml-0.5">*</span></label>
        <input
            :type="type"
            :required="required"
            :value="modelValue"
            :class="['input', { 'font-mono': mono }]"
            @input="$emit('update:modelValue', $event.target.value)"
        >
    </div>
</template>

<script setup>
defineProps({
    label: { type: String, default: '' },
    modelValue: { type: [String, Number], default: '' },
    type: { type: String, default: 'text' },
    span: { type: Number, default: 2 },
    required: { type: Boolean, default: false },
    mono: { type: Boolean, default: false },
});

defineEmits(['update:modelValue']);
</script>
