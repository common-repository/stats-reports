��          �      �       0  	   1     ;  	   D  �  N     �            *        I     W     g     v  �  �     S     \  
   h  �  s     E     a     {  -   �     �     �     �                            	                      
             %s clicks %s views %s visits <ul>
  <li><strong>Top viewed posts </strong>: <pre>&lt;?php stats_most_viewed(); ?&gt;</pre></li>
  <li><strong>Top clicks (outbound links) </strong>: <pre>&lt;?php stats_most_clicked(); ?&gt;</pre></li>
  <li><strong>Top referrers (inbound links) </strong>: <pre>&lt;?php stats_most_incoming(); ?&gt;</pre></li>
  <li><strong>Top search terms </strong>: <pre>&lt;?php stats_most_searched(); ?&gt;</pre></li>
  <li><strong>Defaults arguments</strong> are </strong><pre>limit=5&echo=1&show_title=1&show_description=0&title_li=&lt;depending on function called&gt;&title_before=&lt;h2&gt;&title_after=&lt;/h2&gt;&class=most_&lt;table&gt;&show_count=1</pre>
  where &lt;table&gt; is one of [postviews,clicks,referrers,searchterms]</li>
  <li>You can <strong>display views count for a single post</strong> (Page or Post) with : <pre>&lt;?php if(function_exists('stats_show_views')) stats_show_views(); ?&gt;</pre></li>
  </ul> Cache duration (in hours) Most clicked: Most viewed: Please activate WordPress.com Stats plugin Search terms: Template Tags : Top referrers: WordPress.com Stats Reports Project-Id-Version: zelist
Report-Msgid-Bugs-To: 
POT-Creation-Date: 2009-06-11 18:01+0100
PO-Revision-Date: 
Last-Translator: Malaiac <malaiac@gmail.com>
Language-Team: Malaiac <contact@zelist.net>
MIME-Version: 1.0
Content-Type: text/plain; charset=UTF-8
Content-Transfer-Encoding: 8bit
X-Poedit-Language: French
X-Poedit-Country: FRANCE
X-Poedit-SourceCharset: utf-8
X-Poedit-KeywordsList: __;_e;_c
X-Poedit-Basepath: .
X-Poedit-SearchPath-0: .
 %s clics %s lectures %s visites <ul>
  <li><strong>Billets les plus lus </strong>: <pre>&lt;?php stats_most_viewed(); ?&gt;</pre></li>
  <li><strong>Liens les plus cliqués </strong>: <pre>&lt;?php stats_most_clicked(); ?&gt;</pre></li>
  <li><strong>Meilleurs referrers </strong>: <pre>&lt;?php stats_most_incoming(); ?&gt;</pre></li>
  <li><strong>Termes de recherche les plus fréquents </strong>: <pre>&lt;?php stats_most_searched(); ?&gt;</pre></li>
  <li><strong>Les paramètres par défaut</strong> sont </strong><pre>limit=5&echo=1&show_title=1&show_description=0&title_li=&lt;varie selon la fonction&gt;&title_before=&lt;h2&gt;&title_after=&lt;/h2&gt;&class=most_&lt;table&gt;&show_count=1</pre>
  &lt;table&gt; étant une des statistiques parmi [postviews,clicks,referrers,searchterms]</li>
  <li>Vous pouvez afficher <strong>le nombre de lectures d'un billet précis</strong> (Billet ou Page) avec : <pre>&lt;?php if(function_exists('stats_show_views')) stats_show_views(); ?&gt;</pre></li>
  </ul> Durée du cache (en heures) Liens les plus cliqués : Billets les plus lus : Merci d'activer le plugin WordPress.com stats Termes les plus recherchés : Functions d'affichage Meilleurs liens entrants : Rapports WordPress.com Stats 