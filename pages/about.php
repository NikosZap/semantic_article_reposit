<?php
require_once dirname(__DIR__) . '/config.php';

?>
<!DOCTYPE html>
<html lang="en">
	<head>
<?php include (BASE_PATH . 'includes/header.php'); ?>
		<title>About</title>
	</head>
	<body>
    <?php include (BASE_PATH . 'includes/navbar.php'); ?>
		<main>
			<div class="container py-5">
				<div class="row justify-content-center align-items-center">
					<div class="col-md-10">
						<div class="row shadow p-4 rounded bg-white">
							<div class="col-md-5 text-center border-end">
								<img src="../images/aegean_logo.png" alt="University of the Aegean Logo" class="img-fluid" style="max-height: 300px;">
        </div>
								<div class="col-md-7">
									<h2 class="mb-4 display-5">About this Application</h2>
									<p>
										<strong>Developer:</strong> Nikos Zapantis</p>
									<p>
										<strong>Supervisor:</strong> Kyriakos Kritikos</p>
									<p class="fst-italic">This web application was developed as part of a diploma thesis at the University of the Aegean.</p>
								</div>

								<div class="col-12 mt-4 pt-4 border-top">
									<h3 class="mb-4">Abstract</h3>
									<p class="text-justify">The continuous growth in the volume of scientific information has created a need for systems that support the organization, management, and retrieval of scientific publications. 
										In the context of this diploma thesis, a web repository for the semantic description and discovery of scientific articles was designed and implemented, utilizing Semantic Web technologies and information retrieval mechanisms.</p>

									<p class="text-justify">The application supports the management of scientific publications, authors, and users, as well as the storage and interconnection of the corresponding data. RDF and Apache Jena Fuseki are used for the semantic representation and storage of data, while data access and management are performed through SPARQL queries. 
										In addition, the application integrates external services, such as ORCID for user authentication and Crossref for retrieving and validating publication metadata through DOIs. This architecture enables the interconnection of scientific data and its enrichment with information obtained from external sources.</p>

									<p class="text-justify">Two independent mechanisms are provided for searching scientific publications, allowing users to select their preferred search method. The first is based on Apache Solr and supports searching publication content based on full text, titles, authors, and keywords. The second is a semantic search mechanism that utilizes the stored RDF data and the DBpedia Spotlight service. 
										Through semantic analysis, entities are identified within natural-language text and linked to corresponding DBpedia concepts, which can subsequently be utilized during semantic search.</p>

									<p class="text-justify">The resulting system combines scientific content management, semantic knowledge representation, and different search mechanisms within a unified web environment. 
										The implementation demonstrates the potential of combining Semantic Web technologies, search engines, and external scientific services to support the organization, interconnection, search, and discovery of scientific information.</p>
								</div>
							</div>
						</div>
					</div>
				</div>
			</main>
			<div class="container">
        <?php include (BASE_PATH . 'includes/footer.php'); ?>
			</div>
		</body>
	</html>
