<?php
namespace Phptop50;

class MarkdownTemplate
{
    public function render(array $pl): string
    {
        $md = "# 🐘 PHP GitHub top50 · 第{$pl['week']}期 ({$pl['year']})\n\n";
        $md .= "发布日期：{$pl['date']}  \n排序：Star 数  \n\n";
        foreach ($pl['list'] as $r) {
            $md .= "## #{$r['rank']} {$r['name']}\n";
            $md .= "{$r['desc']}  \n";
            $md .= "⭐ Stars：{$r['star']} · 🔗 [仓库地址]({$r['url']})  \n";
            if ($r['topics']) {
                $md .= '标签：' . implode(' ', array_map(fn($t) => "`{$t}`", $r['topics'])) . "  \n";
            }
            $md .= "\n---\n\n";
        }
        $md .= "*更新时间：" . date('Y-m-d H:i') . "*\n";
        return $md;
    }
}