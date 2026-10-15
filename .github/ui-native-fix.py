from pathlib import Path
p=Path('tests/ui/render-native.php');s=p.read_text()
old="$factory->startSection('content',$content);"
new="$factory->startSection('content', new \\Illuminate\\Support\\HtmlString($content));"
if old in s:
 assert s.count(old)==1
 s=s.replace(old,new);p.write_text(s)
assert new in p.read_text()
