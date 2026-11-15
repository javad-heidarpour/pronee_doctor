import dayjs from "dayjs";
import jalaliday from "jalaliday";
import "dayjs/locale/fa";

dayjs.extend(jalaliday);

/**
 * تبدیل تاریخ به شمسی
 */
export function toPersianDate(date, format = "YYYY/MM/DD") {
    if (!date) return "";

    try {
        return dayjs(date)
            .calendar("jalali")
            .locale("fa")
            .format(format);
    } catch (e) {
        return "";
    }
}

/**
 * نمایش تاریخ و ساعت
 */
export function toPersianDateTime(date, format = "YYYY/MM/DD HH:mm") {
    if (!date) return "";

    try {
        return dayjs(date)
            .calendar("jalali")
            .locale("fa")
            .format(format);
    } catch (e) {
        return "";
    }
}

/**
 * نمایش تاریخ
 */
export function formatDate(date, options = {}) {
    if (!date) return "";

    const {
        withTime = true,
    } = options;

    const format = withTime
        ? "YYYY/MM/DD HH:mm"
        : "YYYY/MM/DD";

    return toPersianDate(date, format);
}

/**
 * تاریخ کوتاه
 */
export function formatDateShort(date) {
    return toPersianDate(date, "YYYY/MM/DD");
}

/**
 * فقط ساعت
 */
export function formatTime(date) {
    if (!date) return "";

    try {
        return dayjs(date).format("HH:mm");
    } catch (e) {
        return "";
    }
}

/**
 * اختلاف روز با امروز
 */
export function daysDiff(date) {
    if (!date) return null;

    return dayjs(date).diff(dayjs(), "day");
}

/**
 * آیا امروز است؟
 */
export function isToday(date) {
    if (!date) return false;

    return dayjs(date).isSame(dayjs(), "day");
}

/**
 * آیا گذشته است؟
 */
export function isPast(date) {
    if (!date) return false;

    return dayjs(date).isBefore(dayjs());
}

/**
 * آیا آینده است؟
 */
export function isFuture(date) {
    if (!date) return false;

    return dayjs(date).isAfter(dayjs());
}

/**
 * اختلاف زمانی خوانا
 * مثال: 5 دقیقه پیش
 */
export function fromNow(date) {
    if (!date) return "";

    const now = dayjs();
    const target = dayjs(date);

    const diffMinutes = now.diff(target, "minute");

    if (diffMinutes < 1) return "همین الان";
    if (diffMinutes < 60) return `${diffMinutes} دقیقه پیش`;

    const diffHours = now.diff(target, "hour");
    if (diffHours < 24) return `${diffHours} ساعت پیش`;

    const diffDays = now.diff(target, "day");
    if (diffDays < 30) return `${diffDays} روز پیش`;

    return toPersianDate(date);
}