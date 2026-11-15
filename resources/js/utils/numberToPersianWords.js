
/**
 * تبدیل عدد به حروف فارسی
 * مثال: 450000 → چهارصد و پنجاه هزار
 */
export default function numberToPersianWords(value) {
    const number = Number(value)

    if (
        value === null ||
        value === undefined ||
        value === '' ||
        !Number.isSafeInteger(number) ||
        number < 0
    ) {
        return ''
    }

    if (number === 0) {
        return 'صفر'
    }

    const ones = [
        '',
        'یک',
        'دو',
        'سه',
        'چهار',
        'پنج',
        'شش',
        'هفت',
        'هشت',
        'نه',
    ]

    const teens = [
        'ده',
        'یازده',
        'دوازده',
        'سیزده',
        'چهارده',
        'پانزده',
        'شانزده',
        'هفده',
        'هجده',
        'نوزده',
    ]

    const tens = [
        '',
        '',
        'بیست',
        'سی',
        'چهل',
        'پنجاه',
        'شصت',
        'هفتاد',
        'هشتاد',
        'نود',
    ]

    const hundreds = [
        '',
        'یکصد',
        'دویست',
        'سیصد',
        'چهارصد',
        'پانصد',
        'ششصد',
        'هفتصد',
        'هشتصد',
        'نهصد',
    ]

    const scales = [
        '',
        'هزار',
        'میلیون',
        'میلیارد',
        'تریلیون',
    ]

    function underThousand(num) {
        const parts = []
        const hundred = Math.floor(num / 100)
        const remainder = num % 100

        if (hundred > 0) {
            parts.push(hundreds[hundred])
        }

        if (remainder >= 10 && remainder < 20) {
            parts.push(teens[remainder - 10])
        } else {
            const ten = Math.floor(remainder / 10)
            const one = remainder % 10

            if (ten > 0) {
                parts.push(tens[ten])
            }

            if (one > 0) {
                parts.push(ones[one])
            }
        }

        return parts.join(' و ')
    }

    const parts = []
    let remaining = number
    let scaleIndex = 0

    while (remaining > 0) {
        const group = remaining % 1000

        if (group > 0) {
            const words = underThousand(group)
            const scale = scales[scaleIndex]

            parts.unshift(
                scale ? `${words} ${scale}` : words
            )
        }

        remaining = Math.floor(remaining / 1000)
        scaleIndex++
    }

    return parts.join(' و ')
}
