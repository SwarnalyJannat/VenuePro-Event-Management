<?php
// Test height calculation
$pct = 50;
echo "HTML snippet:\n";
echo '<div style="display:flex; align-items:flex-end; height:180px;">' . "\n";
echo '  <div style="display:flex; flex-direction:column; align-items:center; flex:1;">' . "\n";
echo '    <span>$5.9k</span>' . "\n";
echo '    <div style="height:' . $pct . '%; width:100%; background:blue;"></div>' . "\n";
echo '    <span>OCT</span>' . "\n";
echo '  </div>' . "\n";
echo '</div>' . "\n";
