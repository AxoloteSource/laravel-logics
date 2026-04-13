<?php

namespace AxoloteSource\Logics\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;

class PublishSkillsCommand extends Command
{
    protected $signature = 'logics:install-skills';

    protected $description = 'Installs AI agent skill guides in the project';

    public function handle()
    {
        $ias = $this->choice(
            'Which AI agents do you use in this project?',
            ['Codex', 'Claude', 'Junie', 'All', 'None'],
            0,
            null,
            true
        );

        if (in_array('None', $ias)) {
            $this->info('No guides will be published.');
            return;
        }

        $skills = [
            'index' => [
                'source' => __DIR__ . '/../../resources/skills/index_logic.md',
                'dir' => 'logics-index',
            ],
            'store' => [
                'source' => __DIR__ . '/../../resources/skills/store_logic.md',
                'dir' => 'logics-store',
            ],
        ];

        foreach ($skills as $skill) {
            $content = File::get($skill['source']);

            if (in_array('Codex', $ias) || in_array('All', $ias)) {
                $this->publishToCodex($content, $skill['dir']);
            }

            if (in_array('Claude', $ias) || in_array('All', $ias)) {
                $this->publishToClaude($content, $skill['dir']);
            }

            if (in_array('Junie', $ias) || in_array('All', $ias)) {
                $this->publishToJunie($content, $skill['dir']);
            }
        }

        $this->info('Skills installed successfully.');
    }

    protected function publishToCodex(string $content, string $skillDir)
    {
        $dir = base_path(".agents/skills/{$skillDir}");
        $path = $dir . '/SKILL.md';
        File::ensureDirectoryExists($dir);
        
        File::put($path, $content);
        $this->line("Published for Codex: .agents/skills/{$skillDir}/SKILL.md");
    }

    protected function publishToClaude(string $content, string $skillDir)
    {
        $dir = base_path(".claude/skills/{$skillDir}");
        $path = $dir . '/SKILL.md';
        File::ensureDirectoryExists($dir);
        
        File::put($path, $content);
        $this->line("Published for Claude: .claude/skills/{$skillDir}/SKILL.md");
    }

    protected function publishToJunie(string $content, string $skillDir)
    {
        $dir = base_path(".junie/skills/{$skillDir}");
        $path = $dir . '/SKILL.md';
        File::ensureDirectoryExists($dir);
        
        File::put($path, $content);
        $this->line("Published for Junie: .junie/skills/{$skillDir}/SKILL.md");
    }
}
