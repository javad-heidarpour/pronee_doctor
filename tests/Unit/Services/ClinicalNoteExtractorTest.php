<?php

declare(strict_types=1);

namespace Tests\Unit\Services;

use App\Services\ClinicalNoteExtractor;
use PHPUnit\Framework\TestCase;

final class ClinicalNoteExtractorTest extends TestCase
{
    public function test_it_extracts_explicit_persian_soap_headings_and_preserves_multiline_content(): void
    {
        $result = (new ClinicalNoteExtractor())->extract(
            "شرح حال: سردرد از دو روز قبل\nتهوع ندارد\nمعاینه: هوشیار و پاسخگو\nارزیابی: نیازمند بررسی بیشتر\nبرنامه درمانی: پیگیری طی یک هفته"
        );

        self::assertSame("سردرد از دو روز قبل\nتهوع ندارد", $result['sections']['subjective']);
        self::assertSame('هوشیار و پاسخگو', $result['sections']['objective']);
        self::assertSame('نیازمند بررسی بیشتر', $result['sections']['assessment']);
        self::assertSame('پیگیری طی یک هفته', $result['sections']['plan']);
        self::assertSame('', $result['sections']['diagnosis']);
        self::assertSame(['diagnosis'], $result['missing_sections']);
        self::assertFalse($result['has_unclassified_text']);
    }

    public function test_it_supports_english_soap_labels(): void
    {
        $result = (new ClinicalNoteExtractor())->extract(
            "S: Patient reports cough\nO: Temperature 38 C\nA: Assessment documented by clinician\nP: Follow-up"
        );

        self::assertSame('Patient reports cough', $result['sections']['subjective']);
        self::assertSame('Temperature 38 C', $result['sections']['objective']);
        self::assertSame('Assessment documented by clinician', $result['sections']['assessment']);
        self::assertSame('Follow-up', $result['sections']['plan']);
    }

    public function test_it_keeps_unlabelled_text_separate_instead_of_guessing_a_section(): void
    {
        $result = (new ClinicalNoteExtractor())->extract("متن بدون برچسب\nشرح حال: درد شکم");

        self::assertSame('متن بدون برچسب', $result['unclassified']);
        self::assertSame('درد شکم', $result['sections']['subjective']);
        self::assertTrue($result['has_unclassified_text']);
        self::assertSame(['objective', 'assessment', 'plan', 'diagnosis'], $result['missing_sections']);
    }

    public function test_it_extracts_diagnosis_only_when_explicitly_labelled(): void
    {
        $result = (new ClinicalNoteExtractor())->extract("تشخیص: عنوان ثبت‌شده توسط پزشک");

        self::assertSame('عنوان ثبت‌شده توسط پزشک', $result['sections']['diagnosis']);
        self::assertSame('', $result['sections']['assessment']);
    }

    public function test_empty_input_returns_all_sections_as_missing(): void
    {
        $result = (new ClinicalNoteExtractor())->extract("  \n ");

        self::assertSame('', $result['unclassified']);
        self::assertSame(['subjective', 'objective', 'assessment', 'plan', 'diagnosis'], $result['missing_sections']);
    }

    public function test_it_does_not_infer_a_diagnosis_or_prescription(): void
    {
        $result = (new ClinicalNoteExtractor())->extract('بیمار سردرد دارد');

        self::assertSame('', $result['sections']['assessment']);
        self::assertSame('', $result['sections']['diagnosis']);
        self::assertSame('', $result['sections']['plan']);
        self::assertContains('No diagnosis or differential diagnosis is inferred.', $result['limitations']);
    }
}
