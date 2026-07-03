<script setup>
import { computed, useAttrs } from 'vue';

defineOptions({
    inheritAttrs: false
});

const props = defineProps({
    modelValue: {
        type: [String, Number],
        default: ''
    },
    label: {
        type: String,
        default: ''
    },
    id: {
        type: String,
        default: () => 'select-' + Math.random().toString(36).substr(2, 9)
    },
    required: {
        type: Boolean,
        default: false
    },
    disabled: {
        type: Boolean,
        default: false
    },
    error: {
        type: [String, Boolean],
        default: false
    },
    size: {
        type: String,
        default: 'default',
        validator: (value) => ['small', 'default'].includes(value)
    }
});

const emit = defineEmits(['update:modelValue', 'change']);

const attrs = useAttrs();

const sizeClasses = computed(() => {
    return props.size === 'small' ? 'h-10 text-body-md py-1' : 'h-12 text-body-md py-2';
});

const borderClasses = computed(() => {
    if (props.error) {
        return 'border-error border-2 focus:border-error focus:ring-1 focus:ring-error';
    }
    return 'border-outline-variant focus:border-primary focus:ring-1 focus:ring-primary';
});

const onChange = (event) => {
    emit('update:modelValue', event.target.value);
    emit('change', event.target.value);
};
</script>

<template>
    <div class="flex flex-col gap-1 w-full">
        <label v-if="label" :for="id" class="block text-sm font-semibold text-on-surface">
            {{ label }} <span v-if="required" class="text-error">*</span>
        </label>
        
        <div class="relative w-full">
            <select
                :id="id"
                :value="modelValue"
                :disabled="disabled"
                :required="required"
                :aria-invalid="error ? 'true' : 'false'"
                @change="onChange"
                v-bind="attrs"
                :class="[
                    'w-full bg-surface border rounded-lg pl-4 pr-12 text-on-surface focus:outline-none transition-all appearance-none cursor-pointer text-ellipsis whitespace-nowrap',
                    sizeClasses,
                    borderClasses,
                    disabled ? 'opacity-50 cursor-not-allowed bg-surface-container' : '',
                    attrs.class || ''
                ]"
            >
                <slot />
            </select>
            
            <span
                class="material-symbols-outlined pointer-events-none absolute right-4 top-1/2 -translate-y-1/2 text-outline"
                :class="[
                    size === 'small' ? '!text-[20px]' : '!text-[24px]',
                    disabled ? 'opacity-50' : ''
                ]"
                aria-hidden="true"
            >
                expand_more
            </span>
        </div>
        
        <p v-if="typeof error === 'string' && error" class="text-xs text-error font-semibold mt-0.5">
            {{ error }}
        </p>
    </div>
</template>
