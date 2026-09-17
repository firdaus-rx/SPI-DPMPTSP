<template>
    <div class="border border-gray-200 rounded-xl overflow-hidden">
        <div class="px-4 py-2.5 border-b" :class="headerClass">
            <h5 class="text-sm font-semibold flex items-center gap-1.5" :class="textClass">
                <span class="w-2 h-2 rounded-full" :class="dotClass"></span>
                {{ title }}
            </h5>
        </div>
        <div class="p-4">
            <slot />
        </div>
    </div>
</template>

<script setup>
import { computed } from 'vue';

const props = defineProps({
    title: { type: String, required: true },
    color: { type: String, default: 'blue' },
});

const colorMap = {
    red: { header: 'bg-red-50 border-red-100', text: 'text-red-700', dot: 'bg-red-500' },
    blue: { header: 'bg-primary-50 border-primary-100', text: 'text-primary-700', dot: 'bg-primary-500' },
    green: { header: 'bg-green-50 border-green-100', text: 'text-green-700', dot: 'bg-green-500' },
    amber: { header: 'bg-amber-50 border-amber-100', text: 'text-amber-700', dot: 'bg-amber-500' },
    purple: { header: 'bg-purple-50 border-purple-100', text: 'text-purple-700', dot: 'bg-purple-500' },
};

const headerClass = computed(() => colorMap[props.color]?.header || colorMap.blue.header);
const textClass = computed(() => colorMap[props.color]?.text || colorMap.blue.text);
const dotClass = computed(() => colorMap[props.color]?.dot || colorMap.blue.dot);
</script>
