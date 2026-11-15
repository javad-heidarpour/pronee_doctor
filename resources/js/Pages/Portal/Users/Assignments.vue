
<script setup>
import { computed } from "vue";
import { Head, Link, useForm, usePage } from "@inertiajs/vue3";
import PortalLayout from "@/Layouts/PortalLayout.vue";

const props = defineProps({
    doctor: Object,
    user: {
        type: Object,
        required: true,
    },
    doctors: {
        type: Array,
        default: () => [],
    },
    permissions: {
        type: Object,
        default: () => ({}),
    },
});

const page = usePage();

const fullName = (item) =>
    [item.name, item.family].filter(Boolean).join(" ");

const permissionList = computed(() =>
    Object.entries(props.permissions).map(([key, label]) => ({
        key,
        label,
    }))
);

const form = useForm({
    doctors: props.doctors.map((doctor) => ({
        id: doctor.id,
        name: doctor.name,
        family: doctor.family,
        phone: doctor.phone,
        assigned: doctor.assigned,
        permissions: [...(doctor.permissions || [])],
    })),
});

const selectedCount = computed(
    () => form.doctors.filter((item) => item.assigned).length
);

const togglePermission = (doctor, permission, checked) => {
    if (checked) {
        if (!doctor.permissions.includes(permission)) {
            doctor.permissions.push(permission);
        }
    } else {
        doctor.permissions = doctor.permissions.filter(
            (item) => item !== permission
        );
    }
};

const hasPermission = (doctor, permission) =>
    doctor.permissions.includes(permission);

const save = () => {
    form.transform((data) => ({
        doctors: data.doctors
            .filter((doctor) => doctor.assigned)
            .map((doctor) => ({
                id: doctor.id,
                permissions: doctor.permissions,
            })),
    })).put(
        route("portal.users.assignments.update", props.user.id),
        {
            preserveScroll: true,
        }
    );
};
</script>

<template>
    <Head title="تخصیص پزشکان و سطح دسترسی منشی" />

    <PortalLayout :doctor="doctor">
        <div dir="rtl" class="w-full p-4 sm:p-6 text-slate-800">
            <div class="mx-auto max-w-5xl space-y-6">

                <!-- Header -->
                <header class="flex flex-wrap items-center justify-between gap-4">
                    <div>
                        <h1 class="text-xl font-black text-slate-800">
                            تخصیص پزشکان به منشی
                        </h1>

                        <p class="mt-2 text-sm text-slate-500">
                            مدیریت پزشکان مرتبط و تعیین مجوزهای دسترسی
                        </p>
                    </div>

                    <Link
                        :href="route('portal.users.index')"
                        class="rounded-xl border border-slate-200 bg-white px-4 py-3 text-sm font-bold text-slate-600 hover:bg-slate-50"
                    >
                        بازگشت به کاربران
                    </Link>
                </header>

                <!-- User Information -->
                <section class="rounded-3xl border border-slate-100 bg-white p-5 shadow-sm">
                    <div class="flex items-center gap-4">
                        <div class="flex h-14 w-14 items-center justify-center rounded-2xl bg-indigo-50 text-2xl">
                            🗂️
                        </div>

                        <div>
                            <div class="font-black text-slate-800">
                                {{ fullName(user) }}
                            </div>

                            <div class="mt-1 text-sm text-slate-500">
                                {{ user.phone }}
                            </div>

                            <div class="mt-1 text-xs text-indigo-600">
                                شناسه کاربر: {{ user.id }}
                            </div>
                        </div>
                    </div>
                </section>

                <!-- Summary -->
                <section class="rounded-2xl border border-indigo-100 bg-indigo-50 p-4">
                    <div class="flex items-center justify-between gap-3">
                        <div>
                            <div class="font-bold text-indigo-900">
                                پزشکان انتخاب‌شده
                            </div>
                            <div class="mt-1 text-xs text-indigo-600">
                                تعداد پزشکانی که این منشی به آن‌ها دسترسی دارد
                            </div>
                        </div>

                        <div class="text-3xl font-black text-indigo-700">
                            {{ selectedCount }}
                        </div>
                    </div>
                </section>

                <!-- Doctors -->
                <section
                    v-for="doctorItem in form.doctors"
                    :key="doctorItem.id"
                    class="overflow-hidden rounded-3xl border bg-white shadow-sm transition"
                    :class="doctorItem.assigned ? 'border-indigo-200' : 'border-slate-100'"
                >
                    <!-- Doctor Header -->
                    <div class="flex flex-wrap items-center justify-between gap-4 p-5">
                        <div class="flex items-center gap-3">
                            <div class="flex h-12 w-12 items-center justify-center rounded-2xl bg-violet-50 text-xl">
                                ⚕️
                            </div>

                            <div>
                                <h2 class="font-black text-slate-800">
                                    {{ fullName(doctorItem) }}
                                </h2>

                                <p class="mt-1 text-xs text-slate-400">
                                    {{ doctorItem.phone || "بدون شماره تماس" }}
                                </p>
                            </div>
                        </div>

                        <label class="flex cursor-pointer items-center gap-2">
                            <input
                                v-model="doctorItem.assigned"
                                type="checkbox"
                                class="h-5 w-5 rounded border-slate-300 text-indigo-600 focus:ring-indigo-500"
                            />

                            <span
                                class="text-sm font-bold"
                                :class="doctorItem.assigned ? 'text-indigo-700' : 'text-slate-500'"
                            >
                                {{ doctorItem.assigned ? "متصل است" : "اتصال به پزشک" }}
                            </span>
                        </label>
                    </div>

                    <!-- Permissions -->
                    <div
                        v-if="doctorItem.assigned"
                        class="border-t border-slate-100 bg-slate-50/70 p-5"
                    >
                        <div class="mb-4">
                            <h3 class="font-bold text-slate-700">
                                سطح دسترسی این منشی
                            </h3>

                            <p class="mt-1 text-xs text-slate-400">
                                مجوزهای این منشی فقط برای این پزشک اعمال می‌شوند.
                            </p>
                        </div>

                        <div class="grid gap-3 sm:grid-cols-2">
                            <label
                                v-for="permission in permissionList"
                                :key="permission.key"
                                class="flex cursor-pointer items-center gap-3 rounded-xl border border-slate-200 bg-white p-3 transition hover:border-indigo-300"
                            >
                                <input
                                    type="checkbox"
                                    :checked="hasPermission(doctorItem, permission.key)"
                                    @change="togglePermission(
                                        doctorItem,
                                        permission.key,
                                        $event.target.checked
                                    )"
                                    class="h-4 w-4 rounded border-slate-300 text-indigo-600 focus:ring-indigo-500"
                                />

                                <span class="text-sm font-medium text-slate-700">
                                    {{ permission.label }}
                                </span>
                            </label>
                        </div>

                        <div class="mt-4 flex justify-between gap-3">
                            <button
                                type="button"
                                @click="doctorItem.permissions = permissionList.map(p => p.key)"
                                class="text-xs font-bold text-indigo-600 hover:text-indigo-800"
                            >
                                انتخاب همه مجوزها
                            </button>

                            <button
                                type="button"
                                @click="doctorItem.permissions = []"
                                class="text-xs font-bold text-rose-600 hover:text-rose-800"
                            >
                                حذف همه مجوزها
                            </button>
                        </div>
                    </div>
                </section>

                <!-- Empty -->
                <div
                    v-if="form.doctors.length === 0"
                    class="rounded-3xl border border-dashed border-slate-300 bg-white p-10 text-center"
                >
                    <div class="text-3xl">⚕️</div>
                    <p class="mt-3 font-bold text-slate-700">
                        پزشک فعالی برای تخصیص وجود ندارد.
                    </p>
                </div>

                <!-- Validation Errors -->
                <div
                    v-if="Object.keys(form.errors).length"
                    class="rounded-2xl border border-rose-200 bg-rose-50 p-4 text-sm text-rose-700"
                >
                    <div
                        v-for="(error, key) in form.errors"
                        :key="key"
                    >
                        {{ error }}
                    </div>
                </div>

                <!-- Save -->
                <div class="sticky bottom-3 rounded-2xl border border-slate-200 bg-white/95 p-4 shadow-lg backdrop-blur">
                    <div class="flex flex-wrap items-center justify-between gap-3">
                        <div class="text-sm text-slate-500">
                            {{ selectedCount }} پزشک انتخاب شده است.
                        </div>

                        <button
                            type="button"
                            @click="save"
                            :disabled="form.processing"
                            class="rounded-xl bg-indigo-600 px-7 py-3 font-bold text-white shadow-lg shadow-indigo-100 transition hover:bg-indigo-700 disabled:cursor-not-allowed disabled:opacity-50"
                        >
                            {{ form.processing ? "در حال ذخیره..." : "ذخیره تغییرات" }}
                        </button>
                    </div>
                </div>

                <!-- Success -->
                <div
                    v-if="page.props.flash?.success"
                    class="rounded-xl bg-emerald-50 p-4 text-sm font-bold text-emerald-700"
                >
                    {{ page.props.flash.success }}
                </div>

            </div>
        </div>
    </PortalLayout>
</template>
