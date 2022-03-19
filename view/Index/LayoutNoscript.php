<noscript>
	<div id="nojavascript">
		<div>
			<?php \str_replace(
                ['{linkstart}', '{linkend}'],
                ['<a href="http://enable-javascript.com/" target="_blank" rel="noreferrer">', '</a>'],
                '此应用程序需要 JavaScript 才能正确运行。请 {linkstart} 启用 JavaScript {linkend} 并重新加载页面'
            ); ?>
		</div>
	</div>
</noscript>