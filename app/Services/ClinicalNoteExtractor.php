<?php

declare(strict_types=1);

namespace App\Services;

final class ClinicalNoteExtractor
{
    /**
     * Extract only explicitly labelled sections. This is deterministic text
     * parsing, not clinical interpretation or generative AI.
     */
    private const LABELS = [
        'subjective' => ['S', 'SUBJECTIVE', 'HPI', 'CC', 'شرح حال', 'شرح‌حال', 'شکایت اصلی', 'علت مراجعه'],
        'objective' => ['O', 'OBJECTIVE', 'EXAM', 'PHYSICAL EXAM', 'معاینه', 'یافته‌های عینی', 'یافته های عینی', 'بررسی‌ها', 'بررسی ها'],
        'assessment' => ['A', 'ASSESSMENT', 'IMPRESSION', 'ارزیابی', 'جمع‌بندی', 'جمع بندی', 'تشخیص'],
        'plan' => ['P', 'PLAN', 'TREATMENT', 'برنامه', 'برنامه درمان', 'برنامه درمانی', 'اقدامات', 'توصیه‌ها', 'توصیه ها', 'پیگیری'],
    ];

    public function extract(string $text): array
    {
        $text = trim(str_replace(["\r\n", "\r"], "\n", $text));
        $sections = [
            'subjective' => [],
            'objective' => [],
            'assessment' => [],
            'plan' => [],
        ];
        $unclassified = [];
        $current = null;

        foreach (preg_split('/\n/u', $text) ?: [] as $line) {
            $trimmed = trim($line);
            if ($trimmed === '') {
                if ($current !== null && $sections[$current] !== []) {
                    $sections[$current][] = '';
                }
                continue;
            }

            $matched = $this->matchHeading($trimmed);
            if ($matched !== null) {
                [$current, $inlineText] = $matched;
                if ($inlineText !== '') {
                    $sections[$current][] = $inlineText;
                }
                continue;
            }

            if ($current === null) {
                $unclassified[] = $trimmed;
            } else {
                $sections[$current][] = $trimmed;
            }
        }

        foreach ($sections as $key => $lines) {
            $sections[$key] = trim(implode("\n", $lines));
        }

        $matchedSections = array_values(array_filter(
            array_keys($sections),
            fn (string $key): bool => $sections[$key] !== ''
        ));

        return [
            'sections' => $sections,
            'unclassified' => implode("\n", $unclassified),
            'matched_sections' => $matchedSections,
            'missing_sections' => array_values(array_diff(array_keys($sections), $matchedSections)),
            'has_unclassified_text' => $unclassified !== [],
            'method' => 'explicit_heading_parser_v1',
            'limitations' => [
                'No diagnosis or differential diagnosis is inferred.',
                'No medication, dose, or treatment recommendation is generated.',
                'Unlabelled text is returned separately and is never silently assigned.',
                'Every extracted section requires clinician review before saving or signing.',
            ],
        ];
    }

    /**
     * @return array{0:string,1:string}|null
     */
    private function matchHeading(string $line): ?array
    {
        $line = preg_replace('/^\s*(?:[-*•]\s*)?/', '', $line) ?? $line;
        $line = preg_replace('/^\*\*(.*?)\*\*\s*/u', '$1 ', $line) ?? $line;

        foreach (self::LABELS as $section => $labels) {
            foreach ($labels as $label) {
                $quoted = preg_quote($label, '/');
                if (preg_match('/^' . $quoted . '\s*(?:\*\*)?\s*[:：\-–]\s*(.*)$/iu', $line, $matches) === 1) {
                    return [$section, trim($matches[1])];
                }
            }
        }

        return null;
    }
}
