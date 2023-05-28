<?php
declare(strict_types=1);

use ElasticAdapter\Indices\Mapping;
use ElasticAdapter\Indices\Settings;
use ElasticMigrations\Facades\Index;
use ElasticMigrations\MigrationInterface;

final class CreateOrganizationsIndex implements MigrationInterface
{
    public function up(): void
    {
        Index::create('organizations', function (Mapping $mapping, Settings $settings) {
            $mapping->text('name', ['analyzer' => 'custom_standard_analyzer_v1']);
            $mapping->text('dba', ['analyzer' => 'custom_standard_analyzer_v1']);
            $mapping->text('ein', ['analyzer' => 'custom_standard_analyzer_v2']);
            $mapping->text('city');
            $mapping->text('state', ['analyzer' => 'exact_match_analyzer']); //
            $mapping->text('postal', ['analyzer' => 'delimeter_analyzer', 'search_analyzer' => 'exact_match_analyzer']);
            $mapping->long('expenses');
            $mapping->keyword('categories');
            $mapping->geoPoint('geo');

            $settings->analysis([
                'filter' => ['my_custom_word_delimiter_filter' => [
                    'type' => 'word_delimiter',
                    'split_on_case_change' => false,
                    'split_on_numerics' => false,
                    'stem_english_possessive' => true,
                    'generate_number_parts' => true,
                    'preserve_original' => true,
                ]],
                'analyzer' => [
                    'custom_standard_analyzer_v1' => [
                        'filter' => ['lowercase', 'asciifolding'],
                        'char_filter' => ['replace_dot', 'replace_quote'],
                        'type' => 'custom',
                        'tokenizer' => 'standard',
                    ],
                    'custom_standard_analyzer_v2' => [
                        'filter' => ['lowercase', 'asciifolding'],
                        'char_filter' => ['specialCharactersFilter'],
                        'type' => 'custom',
                        'tokenizer' => 'standard',
                    ],
                    'exact_match_analyzer' => [
                        'filter' => ['lowercase', 'asciifolding'],
                        'char_filter' => ['replace_dot', 'replace_quote'],
                        'type' => 'custom',
                        'tokenizer' => 'keyword',
                    ],
                    'delimeter_analyzer' => [
                        'tokenizer' => 'keyword',
                        'filter' => ['my_custom_word_delimiter_filter'],
                    ],
                ],
                'char_filter' => [
                    'replace_hyphens_with_space' => ['pattern' => '\-', 'type' => 'pattern_replace', 'replacement' => ' '],
                    'replace_dot' => ['pattern' => '\.', 'type' => 'pattern_replace', 'replacement' => ''],
                    'replace_quote' => ['pattern' => "\'", 'type' => 'pattern_replace', 'replacement' => ''],
                    'specialCharactersFilter' => ['pattern' => '[^A-Za-z0-9]', 'type' => 'pattern_replace', 'replacement' => ''],
                ],
            ]);

            $settings->index([
                'number_of_shards' => 2,
                'number_of_replicas' => 1,
            ]);
        });
    }

    public function down(): void
    {
        Index::dropIfExists('organizations');
    }
}
