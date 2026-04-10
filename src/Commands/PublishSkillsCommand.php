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

        $sourcePath = __DIR__ . '/../../resources/skills/index_logic.md';
        $content = File::get($sourcePath);

        if (in_array('Codex', $ias) || in_array('All', $ias)) {
            $this->publishToCodex($content);
        }

        if (in_array('Claude', $ias) || in_array('All', $ias)) {
            $this->publishToClaude($content);
        }

        if (in_array('Junie', $ias) || in_array('All', $ias)) {
            $this->publishToJunie($content);
        }

        $this->info('Skills published successfully.');
    }

    protected function publishToCodex(string $content)
    {
        $dir = base_path('.agents/skills/logics-index');
        $path = $dir . '/SKILL.md';
        File::ensureDirectoryExists($dir);
        
        File::put($path, $content);
        $this->line('Published for Codex: .agents/skills/logics-index/SKILL.md');
    }

    protected function publishToClaude(string $content)
    {
        $dir = base_path('.claude/skills/logics-index');
        $path = $dir . '/SKILL.md';
        File::ensureDirectoryExists($dir);
        
        File::put($path, $content);
        $this->line('Published for Claude: .claude/skills/logics-index/SKILL.md');
    }

    protected function publishToJunie(string $content)
    {
        $dir = base_path('.junie/skills/logics-index');
        $path = $dir . '/SKILL.md';
        File::ensureDirectoryExists($dir);
        
        File::put($path, $content);
        $this->line('Published for Junie: .junie/skills/logics-index/SKILL.md');
    }
}
