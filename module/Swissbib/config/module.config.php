<?php

namespace Swissbib\Module\Config;

return [
    'router' => [
        'routes' => [
            // ILS location, e.g. baselbern
            'accountWithLocation' => [
                'type'    => 'segment',
                'options' => [
                    'route'       => '/MyResearch/:action/:location',
                    'defaults'    => [
                        'controller' => 'my-research',
                        'action'     => 'Profile',
                        'location'   => 'baselbern'
                    ],
                    'constraints' => [
                        'action'   => '[a-zA-Z][a-zA-Z0-9_-]*',
                        'location' => '[a-z]+',
                    ],
                ]
            ],
            // Search results with tab
            'search-results' => [
                'type'    => 'segment',
                'options' => [
                    'route'    => '/Search/Results[/:tab]',
                    'defaults' => [
                        'controller' => 'Search',
                        'action'     => 'results'
                    ]
                ]
            ],
            // (local) Search User Settings
            'myresearch-settings' => [
                'type'    => 'literal',
                'options' => [
                    'route'    => '/MyResearch/Settings',
                    'defaults' => [
                        'controller' => 'my-research',
                        'action'     => 'settings'
                    ]
                ]
            ],
            'help-page' => [
                'type'    => 'segment',
                'options' => [
                    'route'    => '/HelpPage[/:topic]',
                    'defaults' => [
                        'controller' => 'helppage',
                        'action'     => 'index'
                    ]
                ]
            ],
            'holdings-ajax' => [ // load holdings details for record with ajax
                'type'    => 'segment',
                'options' => [
                    'route'    => '/Holdings/:record/:institution',
                    'defaults' => [
                        'controller' => 'holdings',
                        'action'     => 'list'
                    ]
                ]
            ],
            'holdings-holding-items' => [ // load holding holdings details for record with ajax
                'type'    => 'segment',
                'options' => [
                    'route'    => '/Holdings/:record/:institution/items/:resource',
                    'defaults' => [
                        'controller' => 'holdings',
                        'action'     => 'holdingItems'
                    ]
                ]
            ],
            'myresearch-favorite-institutions' => [ // display defined favorite institutions
                'type'    => 'segment',
                'options' => [
                    'route'    => '/MyResearch/Favorites[/:action]',
                    'defaults' => [
                        'controller' => 'institutionFavorites',
                        'action'     => 'display'
                    ]
                ]
            ],
            'myresearch-favorites' => [ // Override vufind favorites route. Rename to Lists
                'type'    => 'literal',
                'options' => [
                    'route'    => '/MyResearch/Lists',
                    'defaults' => [
                        'controller' => 'my-research',
                        'action'     => 'favorites'
                    ]
                ]
            ],
            'myresearch-photocopies' => [ // Override vufind favorites route. Rename to Lists
              'type'    => 'literal',
              'options' => [
                'route'    => '/MyResearch/Photocopies',
                'defaults' => [
                  'controller' => 'my-research',
                  'action'     => 'photocopies'
                ]
              ]
            ],
            'myresearch-bookings' => [ // Override vufind favorites route. Rename to Lists
              'type'    => 'literal',
              'options' => [
                'route'    => '/MyResearch/Bookings',
                'defaults' => [
                  'controller' => 'my-research',
                  'action'     => 'bookings'
                ]
              ]
            ],
            'myresearch-changeaddress' => [
                'type'    => 'literal',
                'options' => [
                    'route'    => '/MyResearch/Address',
                    'defaults' => [
                        'controller' => 'my-research',
                        'action'     => 'changeAddress'
                    ]
                ]
            ],
            'record-copy' => [
                'type'    => 'segment',
                'options' => [
                    'route'    => '/Record/:id/Copy',
                    'defaults' => [
                        'controller' => 'record',
                        'action'     => 'copy'
                    ]
                ]
            ],
        ]
    ],
    'console' => [
        'router' => [
            'router_class'  => '',
            'routes' => [
                'libadmin-sync' => [
                    'options' => [
                        'route'    => 'libadmin sync [--verbose|-v] [--dry|-d] [--result|-r]',
                        'defaults' => [
                            'controller' => 'libadminsync',
                            'action'     => 'sync'
                        ]
                    ]
                ],
                'libadmin-sync-mapportal' => [
                    'options' => [
                        'route'    => 'libadmin syncMapPortal [--verbose|-v] [--result|-r] [<path>] ',
                        'defaults' => [
                            'controller' => 'libadminsync',
                            'action'     => 'syncMapPortal'
                        ]
                    ]
                ],
                'tab40-import' => [ // Importer for aleph tab40 files
                    'options' => [
                        'route'    => 'tab40import <network> <locale> <source>',
                        'defaults' => [
                            'controller' => 'tab40import',
                            'action'     => 'import'
                        ]
                    ]
                ],
                'hierarchy' => [
                    'options' => [
                        'route'    => 'hierarchy [<limit>] [--verbose|-v]',
                        'defaults' => [
                            'controller' => 'hierarchycache',
                            'action'     => 'buildCache'
                        ]
                    ]
                ]
            ]
        ]
    ],
    'controllers' => [
        'invokables' => [
            'helppage'             => 'Swissbib\Controller\HelpPageController',
            'libadminsync'         => 'Swissbib\Controller\LibadminSyncController',
            'my-research'          => 'Swissbib\Controller\MyResearchController',
            'search'               => 'Swissbib\Controller\SearchController',
            'summon'               => 'Swissbib\Controller\SummonController',
            'holdings'             => 'Swissbib\Controller\HoldingsController',
            'tab40import'          => 'Swissbib\Controller\Tab40ImportController',
            'institutionFavorites' => 'Swissbib\Controller\FavoritesController',
            'hierarchycache'       => 'Swissbib\Controller\HierarchyCacheController',
            'shibtest'             => 'Swissbib\Controller\ShibtestController',
            'ajax'                 => 'Swissbib\Controller\AjaxController',
            'upgrade'              => 'Swissbib\Controller\NoProductiveSupportController',
            'install'              => 'Swissbib\Controller\NoProductiveSupportController',
            'feedback'             => 'Swissbib\Controller\FeedbackController',
            'cover'                => 'Swissbib\Controller\CoverController',
        ],
        'factories'  => [
            'record' => 'Swissbib\Controller\Factory::getRecordController',
            'cart'   => 'VuFind\Controller\Factory::getCartController',
        ]
    ],
    'service_manager' => [
        'invokables' => [
            'VuFindTheme\ResourceContainer'       => 'Swissbib\VuFind\ResourceContainer',
            'Swissbib\QRCode'                     => 'Swissbib\CRCode\QrCodeService',
            'MarcFormatter'                     => 'Swissbib\XSLT\MARCFormatter'
        ],
        'factories' => [
            'Swissbib\HoldingsHelper'                       =>  'Swissbib\RecordDriver\Helper\Factory::getHoldingsHelper',
            'Swissbib\Services\RedirectProtocolWrapper'     =>  'Swissbib\Services\Factory::getProtocolWrapper',
            'Swissbib\TargetsProxy\TargetsProxy'            =>  'Swissbib\TargetsProxy\Factory::getTargetsProxy',
            'Swissbib\TargetsProxy\IpMatcher'               =>  'Swissbib\TargetsProxy\Factory::getIpMatcher',
            'Swissbib\TargetsProxy\UrlMatcher'              =>  'Swissbib\TargetsProxy\Factory::getURLMatcher',

            'Swissbib\Theme\Theme'                          =>  'Swissbib\Services\Factory::getThemeConfigs',
            'Swissbib\Libadmin\Importer'                    =>  'Swissbib\Libadmin\Factory::getLibadminImporter',
            'Swissbib\Tab40Importer'                        =>  'Swissbib\Tab40Import\Factory::getTab40Importer',
            'Swissbib\LocationMap'                          =>  'Swissbib\RecordDriver\Helper\Factory::getLocationMap',
            'Swissbib\EbooksOnDemand'                       =>  'Swissbib\RecordDriver\Helper\Factory::getEbooksOnDemand',
            'Swissbib\Availability'                         =>  'Swissbib\RecordDriver\Helper\Factory::getAvailabiltyHelper',
            'Swissbib\BibCodeHelper'                        =>  'Swissbib\RecordDriver\Helper\Factory::getBibCodeHelper',

            'Swissbib\FavoriteInstitutions\DataSource'      =>  'Swissbib\Favorites\Factory::getFavoritesDataSource',
            'Swissbib\FavoriteInstitutions\Manager'         =>   'Swissbib\Favorites\Factory::getFavoritesManager',
            'Swissbib\ExtendedSolrFactoryHelper'            =>  'Swissbib\VuFind\Search\Helper\Factory::getExtendedSolrFactoryHelper',
            'Swissbib\TypeLabelMappingHelper'               =>  'Swissbib\VuFind\Search\Helper\Factory::getTypeLabelMappingHelper',

            'Swissbib\Highlight\SolrConfigurator'           =>  'Swissbib\Services\Factory::getSOLRHighlightingConfigurator',
            'Swissbib\Logger'                               =>  'Swissbib\Services\Factory::getSwissbibLogger',
            'Swissbib\RecordDriver\SolrDefaultAdapter'      =>  'Swissbib\RecordDriver\Factory::getSolrDefaultAdapter',
            'VuFind\Export'                                 =>  'Swissbib\Services\Factory::getExport',
            'sbSpellingProcessor'                           =>  'Swissbib\VuFind\Search\Solr\Factory::getSpellchecker',
            'sbSpellingResults'                             =>  'Swissbib\VuFind\Search\Solr\Factory::getSpellingResults',

            'Swissbib\Hierarchy\SimpleTreeGenerator'        =>  'Swissbib\Hierarchy\Factory::getSimpleTreeGenerator',
            'Swissbib\Hierarchy\MultiTreeGenerator'         =>  'Swissbib\Hierarchy\Factory::getMultiTreeGenerator',

        'VuFind\SearchOptionsPluginManager'                 => 'Swissbib\Services\Factory::getSearchOptionsPluginManager',
            'VuFind\SearchParamsPluginManager'              => 'Swissbib\Services\Factory::getSearchParamsPluginManager',
            'VuFind\SearchResultsPluginManager'             => 'Swissbib\Services\Factory::getSearchResultsPluginManager',

            'Swissbib\SearchTabsHelper'                     =>  'Swissbib\View\Helper\Swissbib\Factory::getSearchTabsHelper',
            //'VuFind\SearchTabsHelper'                       =>  'Swissbib\View\Helper\Root\Factory::getSearchTabsHelper',
            'Swissbib\Record\Form\CopyForm'                 =>  'Swissbib\Record\Factory::getCopyForm',
            'Swissbib\MyResearch\Form\AddressForm'          =>  'Swissbib\MyResearch\Factory::getAddressForm',
            'Swissbib\Feedback\Form\FeedbackForm'           =>  'Swissbib\Feedback\Factory::getFeedbackForm',
        ]
    ],
    'view_helpers'    => [
        'invokables' => [
            'Authors'                        => 'Swissbib\View\Helper\Authors',
            'facetItem'                      => 'Swissbib\View\Helper\FacetItem',
            'facetItemLabel'                 => 'Swissbib\View\Helper\FacetItemLabel',
            'lastSearchWord'                 => 'Swissbib\View\Helper\LastSearchWord',
            'lastTabbedSearchUri'            => 'Swissbib\View\Helper\LastTabbedSearchUri',
            'mainTitle'                      => 'Swissbib\View\Helper\MainTitle',
            'myResearchSideBar'              => 'Swissbib\View\Helper\MyResearchSideBar',
            'urlDisplay'                     => 'Swissbib\View\Helper\URLDisplay',
            'number'                         => 'Swissbib\View\Helper\Number',
            'physicalDescription'            => 'Swissbib\View\Helper\PhysicalDescriptions',
            'removeHighlight'                => 'Swissbib\View\Helper\RemoveHighlight',
            'subjectHeadingFormatter'        => 'Swissbib\View\Helper\SubjectHeadings',
            'SortAndPrepareFacetList'        => 'Swissbib\View\Helper\SortAndPrepareFacetList',
            'tabTemplate'                    => 'Swissbib\View\Helper\TabTemplate',
            'zendTranslate'                  => 'Zend\I18n\View\Helper\Translate',
            'getVersion'                     => 'Swissbib\View\Helper\GetVersion',
            'holdingActions'                 => 'Swissbib\View\Helper\HoldingActions',
            'availabilityInfo'               => 'Swissbib\View\Helper\AvailabilityInfo',
            'transLocation'                  => 'Swissbib\View\Helper\TranslateLocation',
            'qrCodeHolding'                  => 'Swissbib\View\Helper\QrCodeHolding',
            'holdingItemsPaging'             => 'Swissbib\View\Helper\HoldingItemsPaging',
            'filterUntranslatedInstitutions' => 'Swissbib\View\Helper\FilterUntranslatedInstitutions',
            'configAccess'                   => 'Swissbib\View\Helper\Config',
            'layoutClass'                    => 'Swissbib\View\Helper\LayoutClass'
        ],
        'factories'  => [
            'institutionSorter'                         =>  'Swissbib\View\Helper\Factory::getInstitutionSorter',
            'extractFavoriteInstitutionsForHoldings'    =>  'Swissbib\View\Helper\Factory::getFavoriteInstitutionsExtractor',
            'institutionDefinedAsFavorite'              =>  'Swissbib\View\Helper\Factory::getInstitutionsAsDefinedFavorites',
            'qrCode'                                    =>  'Swissbib\View\Helper\Factory::getQRCodeHelper',
            'isFavoriteInstitution'                     =>  'Swissbib\View\Helper\Factory::isFavoriteInstitutionHelper',
            'domainURL'                                 =>  'Swissbib\View\Helper\Factory::getDomainURLHelper',
            'redirectProtocolWrapper'                   =>  'Swissbib\View\Helper\Factory::getRedirectProtocolWrapperHelper'
        ]
    ],
    'vufind' => [
        'recorddriver_tabs' => [
            'VuFind\RecordDriver\Summon'   => [
                'tabs' => [
                    'Description'  => 'articledetails',
                    'TOC'          => null, // Disable TOC tab
                ]
            ]
        ],
        // This section contains service manager configurations for all VuFind
        // pluggable components:
        'plugin_managers' => [
            'search_backend'           => [
                'factories' => [
                    'Solr'   => 'Swissbib\VuFind\Search\Factory\SolrDefaultBackendFactory',
                    'Summon' => 'Swissbib\VuFind\Search\Factory\SummonBackendFactory',
                ]
            ],
            'auth'                     => [
                'factories' => [
                    'shibbolethmock' => 'Swissbib\VuFind\Auth\Factory::getShibMock',
                ],
                'invokables' => [
                    'shibboleth'    => 'Swissbib\VuFind\Auth\Shibboleth',
                ],
            ],
            'autocomplete' => [
                'factories' => [
                    'solr'          =>  'Swissbib\VuFind\Autocomplete\Factory::getSolr',
                ],
            ],
            'content_covers' => [
                'factories' => [
                    'amazon' => 'Swissbib\Content\Covers\Factory::getAmazon',
                ],
            ],
            'recommend' => [
                'factories' => [
                    'favoritefacets' => 'Swissbib\Services\Factory::getFavoriteFacets',
                    'sidefacets' => 'Swissbib\Recommend\Factory::getSideFacets',
                    'topiprange' => 'Swissbib\Recommend\Factory::getTopIpRange'
                ],
            ],
            'recorddriver'             => [
                'factories' => [
                    'solrmarc' => 'Swissbib\RecordDriver\Factory::getSolrMarcRecordDriver',
                    'summon'   => 'Swissbib\RecordDriver\Factory::getSummonRecordDriver',
                    'worldcat' => 'Swissbib\RecordDriver\Factory::getWorldCatRecordDriver',
                    'missing'  => 'Swissbib\RecordDriver\Factory::getRecordDriverMissing',
                ]
            ],
            'ils_driver'               => [
                'factories' => [
                    'aleph' => 'Swissbib\VuFind\ILS\Driver\Factory::getAlephDriver',
                    'multibackend' => 'Swissbib\VuFind\ILS\Driver\Factory::getMultiBackend',
                ]
            ],
            'hierarchy_driver'         => [
                'factories' => [
                    'series' => 'Swissbib\VuFind\Hierarchy\Factory::getHierarchyDriverSeries',
                ]
            ],
            'hierarchy_treedataformatter' => [
                'invokables' => [
                    'json' => 'Swissbib\VuFind\Hierarchy\TreeDataFormatter\Json',
                ],
            ],
            'hierarchy_treerenderer'   => [
                'factories' => [
                    'jstree' => 'Swissbib\VuFind\Hierarchy\Factory::getJSTree'
                ]
            ],
            'recordtab'                => [
                'invokables' => [
                    'articledetails' => 'Swissbib\RecordTab\ArticleDetails',
                    'description'    => 'Swissbib\RecordTab\Description'
                ]
            ],
        ]
    ],
    'swissbib' => [
        // The ignore patterns have to be valid regex!
        'ignore_css_assets' => [
            //can be used to ignore assets like this:
            //'|blueprint/screen.css|',
        ],
        'ignore_js_assets'  => [
            //can be used to ignore assets like this:
            //'|jquery\.min.js|', // jquery 1.6
            //'|^jquery\.form\.js|',
        ],
        // This section contains service manager configurations for all Swissbib
        // pluggable components:
        'plugin_managers' => [
            'vufind_search_options' => [
                'abstract_factories' => ['Swissbib\VuFind\Search\Options\PluginFactory'],
            ],
            'vufind_search_params'  => [
                'abstract_factories' => ['Swissbib\VuFind\Search\Params\PluginFactory'],
            ],
            'vufind_search_results' => [
                'abstract_factories' => ['Swissbib\VuFind\Search\Results\PluginFactory'],
                'factories' => [
                    'favorites' => 'Swissbib\VuFind\Search\Results\Factory::getFavorites',
                ],
            ]
        ],
    ]
];
