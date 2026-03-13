<?php
require_once dirname(__DIR__) . '/config.php';
require_once BASE_PATH . 'includes/pub_sparql_queries.php';

$uri = $_GET['uri'] ?? null;
if (!$uri) {
    echo "<p>Error: No publication URI provided.</p>";
    exit;
}

if ($_SESSION['user']['role'] !== 'Admin') {
    $_SESSION['unauthorized'] = true;
    header("Location: ../pages/login.php");
    exit;
}

$publication = getPublicationByURI($uri);
if (!$publication) {
    echo "<p>Error: Publication not found.</p>";
    exit;
}
$authors = getAuthorsByPublication($uri);
if (!$publication) {
    echo "<p>Error: Authors not found.</p>";
    exit;
}

$keywords = getKeywordsByPublication($uri);

$fieldLabels = [
    'title' => 'Title',
    'type' => 'Type',
    'doi' => 'DOI',
    'publicationDate' => 'Publication Date',
    'uploadedDate' => 'Upload Date',
    'uploadedAt' => 'Update Date',
    'filePath' => 'Uploaded File',
    'abstract' => 'Abstract',
    'isApproved' => 'Approval Status',
    'journal' => 'Journal',
    'volume' => 'Volume',
    'pages' => 'Pages',
    'publisher' => 'Publisher',
    'isbn' => 'ISBN',
    'school' => 'School',
    'supervisor' => 'Supervisor',
    'firstname' => 'Firstname',
    'lastname' => 'Lastname',
    'orcid' => 'ORCID',
    'abstract' => 'Abstract'];

$skipKeys = ['userURI', 
             'username', 
             'firstname', 
             'lastname', 
             'email', 
             'hasUploaded',
             'abstract',
             'hasAuthor',
             'hasKeyword'];

?>
<!DOCTYPE html>
<html lang="en">
	<head>
    <?php include (BASE_PATH . 'includes/header.php'); ?>
		<title>Review Publication</title>
	</head>
	<body>
<?php include (BASE_PATH . 'includes/navbar.php'); ?>
		<main>
			<div class="container mt-5">
				<h3 class="display-6">Review Publication</h3>
				<div id="message"/>
				<div class="table-responsive">
					<h5 class="mt-4">Publication</h5>
					<table class="table table-bordered">
        <?php foreach ($publication as $key => $value): 
            if (in_array($key, $skipKeys)) continue; ?>
						<tr>
							<th><?= $fieldLabels[$key] ?? htmlspecialchars($key) ?>
							</th>
							<td>
                    <?php if ($key === 'filePath'): 
                        $filePath = BASE_PATH . 'pending/' . htmlspecialchars($value); ?>
								<a class="btn btn-outline-primary" onclick="openPdfModal('../pending/<?= htmlspecialchars($value) ?>')">View PDF <i class="bi bi-file-earmark-pdf"/>
								</a>
                    <?php elseif($key === 'type'): ?>
                        <?= htmlspecialchars(extractSuffix($value)) ?>
                    <?php elseif($key === 'isApproved'):
                            if ($value === 'true'):?>
								<span class='badge bg-success'>Yes</span> 
                            <?php else:?>
								<span class='badge bg-danger'>No</span>
                            <?php endif; ?>    
                    <?php elseif($key === 'title'): ?>
                            <?= $title = $value;
                                htmlspecialchars(extractSuffix($value)); ?>
                            <?php else: ?>    
                        <?= htmlspecialchars($value) ?>
                    <?php endif; ?>
							</td>
						</tr>
        <?php endforeach; ?>
        <?php if (!empty($publication['firstname']) && !empty($publication['lastname'])): ?>
						<tr>
							<th>Submitted By</th>
							<td>
                <?= htmlspecialchars($publication['firstname'] . ' ' . $publication['lastname']) ?>
                (<?= htmlspecialchars($publication['email'] ?? '') ?>)
            </td>
						</tr>
    <?php endif; ?>
    <?php if (!empty($keywords)): ?>
						<tr>
							<th>Keywords</th>
							<td>
                <?php foreach ($keywords as $kw): ?>
								<span class="badge bg-secondary me-1"><?= htmlspecialchars($kw) ?>
								</span>
                <?php endforeach; ?>
							</td>
						</tr>
        <?php endif; ?>
						<tr>
							<th>Abstract</th>
							<td>
            <?php echo isset($publication['abstract']) ? htmlspecialchars($publication['abstract']) : '<span class="text-muted">No abstract provided.</span>';
            $abstract = $publication['abstract'] ?? null; ?>
							</td>
						</table>

    <?php if (!empty($authors)): ?>
						<h5 class="mt-4">Authors</h5>
						<div class="table-responsive">
							<table class="table table-striped table-bordered">
								<thead>
									<tr>
										<th>#</th>
										<th>Full Name</th>
										<th>ORCID</th>
									</tr>
								</thead>
								<tbody>
                <?php foreach ($authors as $index => $author): ?>
									<tr>
										<td><?= $index + 1 ?>
										</td>
										<td><?= htmlspecialchars($author['firstname']) . ' ' . htmlspecialchars($author['lastname']) ?>
										</td>
										<td>
                            <?php if (!empty($author['orcid'])): ?>
											<a href="https://orcid.org/<?= htmlspecialchars($author['orcid']) ?>" target="_blank">
                                    <?= htmlspecialchars($author['orcid']) ?>
											</a>
                            <?php else: ?>
											<span class="text-muted">N/A</span>
                            <?php endif; ?>
										</td>
									</tr>
                <?php endforeach; ?>
								</tbody>
							</table>
						</div>
<?php endif; ?>
					</div>
					<div class="d-flex gap-2 mt-3 justify-content-between">
        <?php if ($publication['isApproved']  === 'false'){ ?>
						<button onclick="handleAction('approve')" class="btn btn-success flex-fill">
							<i class="bi bi-check-circle"/> Approve
        </button>
						<button onclick="handleAction('crossref')" class="btn btn-primary flex-fill" data-bs-toggle="modal" data-bs-target="#crossrefModal">
							<i class="bi bi-journal-check"/> CrossRef 
        </button>
						<button onclick="handleAction('reject')" class="btn btn-danger flex-fill" data-bs-toggle="modal" data-bs-target="#rejectModal">
							<i class="bi bi-trash"/> Reject
        </button> 
        <?php }elseif($publication['isApproved']  === 'true'){ ?>
						<button onclick="handleAction('delete')" class="btn btn-danger flex-fill">
							<i class="bi bi-trash"/> Delete
        </button> 
        <?php }?>
					</div>
				</div>
			</main>
			<div class="container">
        <?php include (BASE_PATH . 'includes/footer.php'); ?>
			</div>

<?php

$userAuthors = [];
if (!empty($authors) && is_array($authors)) {
    foreach ($authors as $a) {
        $fn = trim($a['firstname'] ?? $a['firstName'] ?? '');
        $ln = trim($a['lastname'] ?? $a['lastName'] ?? '');
        $full = trim($fn . ' ' . $ln);
        if ($full !== '') $userAuthors[] = $full;
    }
}

$userMeta = [
    'title' => $publication['title'] ?? '',
    'doi' => $publication['doi'] ?? '',
    'type' => $publication['type'] ?? '',
    'publicationDate' => $publication['publicationDate'] ?? '',
    'journal' => $publication['journal'] ?? '',
    'publisher' => $publication['publisher'] ?? '',
    'volume' => $publication['volume'] ?? '',
    'pages' => $publication['pages'] ?? '',
    'isbn' => $publication['isbn'] ?? '',
    'issn' => $publication['issn'] ?? '',
    'authors' => $userAuthors,
];
?>
			<script>

function handleAction(action) {

    const USER_META = <?= json_encode($userMeta, JSON_UNESCAPED_UNICODE); ?>;

     if (action === 'reject') {
      const modalEl = document.getElementById('rejectModal');
      const modal = bootstrap.Modal.getOrCreateInstance(modalEl);
      modal.show();
      return;
    }

    if(action === 'reject' || action === 'approve') {

    if (action === 'reject' && !confirm("Are you sure you want to reject this publication?")) return;

    fetch('../backend/approve_publications_backend.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
        body: new URLSearchParams({ action, uri: "<?= $uri ?>", title: "<?= $title ?>", filePath: "<?= addslashes($filePath) ?>" ,  abstract: "<?= addslashes($abstract) ?>"})
    })
    .then(res => res.json())
    .then(data => {
        if (data.status === 'success') {
            window.location.href = 'approve_publications.php?message=' + encodeURIComponent(data.message);
        } else {
            document.getElementById("message").innerHTML = '<div class="alert alert-danger">' + data.message + '</div>';
        }
    })
    .catch(() => {
        document.getElementById("message").innerHTML = '<div class="alert alert-danger">Request failed.</div>';
    });
    }else if(action === 'delete') {
        fetch('../backend/manage_publications_backend.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
        body: new URLSearchParams({ action, uri: "<?= $uri ?>"})
    })
    .then(res => res.json())
    .then(data => {
        if (data.status === 'success') {
            window.location.href = 'manage_publications.php?message=' + encodeURIComponent(data.message);
        } else {
            document.getElementById("message").innerHTML = '<div class="alert alert-danger">' + data.message + '</div>';
        }
    })
    .catch(() => {
        document.getElementById("message").innerHTML = '<div class="alert alert-danger">Request failed.</div>';
    });
    }

    if (action === 'crossref') {
  const body = document.getElementById('crossrefComparisonBody');

  const scoreBox = document.getElementById('crossrefMatchScore');
if (scoreBox) {
  scoreBox.innerHTML = `Match Score: —`;
}

  // 1) show loading
  body.innerHTML = `
    <div class="text-center py-4">
					<div class="spinner-border"/>
					<p class="mt-2">Loading Crossref metadata...</p>
				</div>
  `;

  const doi = (USER_META.doi || '').trim();
  if (!doi) {
    body.innerHTML = `<div class="alert alert-warning mb-0">No DOI found for this publication.</div>`;
    return;
  }

  // 2) fetch crossref normalized meta
  fetch(`../backend/crossref_compare_backend.php?doi=${encodeURIComponent(doi)}`)
    .then(res => res.json())
    .then(data => {
      if (data.status !== 'success') {
        body.innerHTML = `<div class="alert alert-danger mb-0">${escapeHtml(data.message || 'Crossref error')}</div>`;
        return;
      }
      const crossrefMeta = data.crossref || {};
      body.innerHTML = renderComparisonTable(USER_META, crossrefMeta);

        const score = calculateMatchScore(USER_META, crossrefMeta);
        renderMatchScore(score);
      
    
    })
    .catch((e) => {
      renderMatchScore(0);
      console.error('renderComparisonTable error:', e);  
      body.innerHTML = `<div class="alert alert-danger mb-0">Request failed.</div>`;
    });

  return; // important: stop other branches
}
}

function openPdfModal(pdfUrl) {
    const frame = document.getElementById('pdfFrame');
    frame.src = pdfUrl;
    const modal = new bootstrap.Modal(document.getElementById('pdfModal'));
    modal.show();
  }

function escapeHtml(str) {
  return String(str)
    .replaceAll('&', '&amp;')
    .replaceAll('<', '&lt;') .replaceAll('>','&gt;' ) .replaceAll('"','&quot;' ) .replaceAll("'",'&#039;' ); } function normalizeValue(v) { if (v===null || v===undefined) return'' ; if (Array.isArray(v)) return v.map(x=> String(x).trim()).filter(Boolean).sort().join(', ');
  return String(v).trim();
}

function comparable(v) {
  return normalizeValue(v).toLowerCase().replace(/\s+/g, ' ');
}

function normalizeType(type) {
  if (!type) return '';

  const t = String(type).toLowerCase().trim();

  if (t.includes('#article')) return 'journal-article';
  if (t === 'article') return 'journal-article';

  return t;
}

function normalizeAuthorName(name) {
  if (!name) return '';

  name = String(name)
    .toLowerCase()
    .replace(/\./g, '')
    .replace(/,/g, ' ')
    .replace(/\s+/g, ' ')
    .trim();

  const parts = name.split(' ').filter(Boolean);
  if (parts.length === 0) return '';
  if (parts.length === 1) return parts[0];

  const lastname = parts[parts.length - 1];
  const firstnameInitial = parts[0][0];

  return `${lastname}_${firstnameInitial}`;
}

function normalizeAuthors(authors) {
  if (!authors) return [];

  if (typeof authors === 'string') {
    authors = authors.split(',');
  }

  return authors
    .map(a => normalizeAuthorName(a))
    .filter(Boolean)
    .sort();
}

function cleanAuthorText(name) {
  return String(name || '')
    .toLowerCase()
    .replace(/\./g, '')
    .replace(/&/g, ' and ')
    .replace(/,/g, ' ')
    .replace(/\s+/g, ' ')
    .trim();
}

function parseAuthorName(name) {
  const original = String(name || '').trim();
  const cleaned = cleanAuthorText(name);

  if (!cleaned) {
    return null;
  }

  const parts = cleaned.split(' ').filter(Boolean);
  if (parts.length === 0) {
    return null;
  }

  const first = parts[0];
  const initials = first ? first.charAt(0) : '';

  let surname = '';
  let surnameParts = [];

  if (parts.length === 1) {
    surname = parts[0];
    surnameParts = [parts[0]];
  } else if (parts.length === 2) {
    surname = parts[1];
    surnameParts = [parts[1]];
  } else {
    surnameParts = parts.slice(1);
    surname = surnameParts[surnameParts.length - 1];
  }

  return {
    original,
    cleaned,
    first,
    initials,
    parts,
    surname,
    surnameParts
  };
}

function buildAuthorEntries(authors) {
  if (!authors) return [];

  if (typeof authors === 'string') {
    authors = authors.split(',');
  }

  return authors
    .map(a => parseAuthorName(a))
    .filter(Boolean);
}

function exactAuthorMatch(a, b) {
  if (!a || !b) return false;

  const aToken = `${a.surname}_${a.initials}`;
  const bToken = `${b.surname}_${b.initials}`;

  return aToken === bToken;
}

function fuzzyAuthorMatch(a, b) {
  if (!a || !b) return false;

  if (!a.initials || !b.initials) return false;
  if (a.initials !== b.initials) return false;

  const aSurname = a.surname;
  const bSurname = b.surname;

  const aSurnameParts = a.surnameParts.join(' ');
  const bSurnameParts = b.surnameParts.join(' ');

  // ίδιο τελικό επώνυμο + ίδιο αρχικό μικρού ονόματος
  if (aSurname === bSurname) {
    return true;
  }

  // περιπτώσεις όπως "corchado garcia" vs "corchado"
  if (aSurnameParts.includes(bSurname) || bSurnameParts.includes(aSurname)) {
    return true;
  }

  // fallback: ένα surname περιέχεται στο άλλο
  if (aSurname.includes(bSurname) || bSurname.includes(aSurname)) {
    return true;
  }

  return false;
}

function compareAuthors(userAuthors, crossrefAuthors) {
  const userEntries = buildAuthorEntries(userAuthors);
  const crossrefEntries = buildAuthorEntries(crossrefAuthors);

  const usedUser = new Set();
  const usedCross = new Set();

  const exactMatches = [];
  const fuzzyMatches = [];

  // 1. exact matches
  for (let i = 0; i < crossrefEntries.length;i++) { for (let j=0; j
					< userEntries.length; j++) { if (usedCross.has(i) || usedUser.has(j)) continue; if (exactAuthorMatch(crossrefEntries[i], userEntries[j])) { usedCross.add(i); usedUser.add(j); exactMatches.push({ crossref: crossrefEntries[i].original, user: userEntries[j].original }); break; } } } 2. fuzzy matches for (let i=0; i
					< crossrefEntries.length; i++) { for (let j=0; j
					< userEntries.length; j++) { if (usedCross.has(i) || usedUser.has(j)) continue; if (fuzzyAuthorMatch(crossrefEntries[i], userEntries[j])) { usedCross.add(i); usedUser.add(j); fuzzyMatches.push({ crossref: crossrefEntries[i].original, user: userEntries[j].original }); break; } } } const missingInUser=[]; const extraInUser=[]; for (let i=0; i
					< crossrefEntries.length; i++) { if (!usedCross.has(i)) { missingInUser.push(crossrefEntries[i].original); } } for (let j=0; j
					< userEntries.length; j++) { if (!usedUser.has(j)) { extraInUser.push(userEntries[j].original); } } return { status: missingInUser.length===0 && extraInUser.length===0 ?'match' :'different' , exactMatches, fuzzyMatches, missingInUser, extraInUser }; } function rowHtml(field, userVal, crossVal) { const label=field.label; const key=field.key; const rawUser=userVal; const rawCross=crossVal; let u=normalizeValue(userVal); let c=normalizeValue(crossVal); let badge=`<span class="badge bg-secondary">N/A</span>`;
  let rowClass = '';
  let extraInfo = '';

  if (key === 'authors') {
    const hasUser = Array.isArray(rawUser) ? rawUser.length > 0 : comparable(rawUser) !== '';
    const hasCross = Array.isArray(rawCross) ? rawCross.length > 0 : comparable(rawCross) !== '';

    if (!hasUser && !hasCross) {
      badge = `<span class="badge bg-secondary">N/A</span>`;
    } else if (!hasUser && hasCross) {
      badge = `<span class="badge bg-info text-dark">Missing in User</span>`;
      rowClass = 'table-info';
    } else if (hasUser && !hasCross) {
      badge = `<span class="badge bg-info text-dark">Missing in Crossref</span>`;
      rowClass = 'table-info';
    } else {
      const result = compareAuthors(rawUser, rawCross);

      if (result.status === 'match') {
        badge = `<span class="badge bg-success">Match</span>`;
      } else {
        badge = `<span class="badge bg-warning text-dark">Different</span>`;
        rowClass = 'table-warning';
      }

      if (result.fuzzyMatches.length > 0) {
        extraInfo += `
          <div class="small text-primary mt-2">
						<strong>Possible matches:</strong>
						<br>
            ${result.fuzzyMatches.map(m => `${escapeHtml(m.crossref)} ↔ ${escapeHtml(m.user)}`).join('<br>')}
          </div>
        `;
      }

      if (result.missingInUser.length > 0) {
        extraInfo += `
          <div class="small text-danger mt-2">
								<strong>Missing in User:</strong> ${escapeHtml(result.missingInUser.join(', '))}
          </div>
        `;
      }

      if (result.extraInUser.length > 0) {
        extraInfo += `
          <div class="small text-warning mt-1">
								<strong>Extra in User:</strong> ${escapeHtml(result.extraInUser.join(', '))}
          </div>
        `;
      }
    }
  } else {
    let compU = u;
    let compC = c;

    if (key === 'type') {
      compU = normalizeType(u);
      compC = normalizeType(c);
    } else {
      compU = comparable(u);
      compC = comparable(c);
    }

    const isEmptyU = compU === '';
    const isEmptyC = compC === '';
    const same = (!isEmptyU || !isEmptyC) && compU === compC;

    if (!isEmptyU || !isEmptyC) {
      if (same) {
        badge = `<span class="badge bg-success">Match</span>`;
      } else {
        badge = `<span class="badge bg-warning text-dark">Different</span>`;
        rowClass = 'table-warning';
      }

      if (!isEmptyU && isEmptyC) {
        badge = `<span class="badge bg-info text-dark">Missing in Crossref</span>`;
        rowClass = 'table-info';
      }

      if (isEmptyU && !isEmptyC) {
        badge = `<span class="badge bg-info text-dark">Missing in User</span>`;
        rowClass = 'table-info';
      }
    }
  }

  return `
    <tr class="${rowClass}">
								<td class="fw-semibold">
        ${escapeHtml(label)}
        <div class="mt-2">${badge}</div>
								</td>
								<td>
        ${u !== '' ? escapeHtml(u) : '<span class="text-muted">—</span>'}
      </td>
								<td>
        ${c !== '' ? escapeHtml(c) : '<span class="text-muted">—</span>'}
        ${extraInfo}
      </td>
							</tr>
  `;
}

function renderComparisonTable(userMeta, crossrefMeta) {
  const fields = [
    { key: 'title', label: 'Title' },
    { key: 'doi', label: 'DOI' },
    { key: 'type', label: 'Type' },
    { key: 'publicationDate', label: 'Publication Date' },
    { key: 'journal', label: 'Journal / Container Title' },
    { key: 'publisher', label: 'Publisher' },
    { key: 'volume', label: 'Volume' },
    { key: 'pages', label: 'Pages' },
    { key: 'isbn', label: 'ISBN' },
    { key: 'issn', label: 'ISSN' },
    { key: 'authors', label: 'Authors' }
  ];

  const rows = fields
    .map(field => rowHtml(field, userMeta[field.key], crossrefMeta[field.key]))
    .join('');

  return `
    <div class="mb-3">
								<div class="d-flex flex-wrap gap-2 align-items-center">
									<span class="badge bg-secondary">N/A</span>
									<span class="badge bg-success">Match</span>
									<span class="badge bg-warning text-dark">Different</span>
									<span class="badge bg-info text-dark">Missing</span>
								</div>
							</div>
							<div class="table-responsive">
								<table class="table table-bordered align-middle">
									<thead class="table-light">
										<tr>
											<th style="width: 22%;">Field</th>
											<th style="width: 39%;">User Metadata</th>
											<th style="width: 39%;">Crossref Metadata</th>
										</tr>
									</thead>
									<tbody>
          ${rows}
        </tbody>
								</table>
							</div>
  `;
}

function calculateMatchScore(userMeta, crossrefMeta) {
  const fields = [
    'title',
    'doi',
    'type',
    'publicationDate',
    'journal',
    'publisher',
    'volume',
    'pages',
    'isbn',
    'issn',
    'authors'
  ];

  let matched = 0;
  let total = 0;

  fields.forEach(key => {
    const userVal = userMeta[key];
    const crossVal = crossrefMeta[key];

    if (key === 'authors') {
      const hasUser = Array.isArray(userVal) ? userVal.length > 0 : comparable(userVal) !== '';
      const hasCross = Array.isArray(crossVal) ? crossVal.length > 0 : comparable(crossVal) !== '';

      if (!hasUser && !hasCross) {
        return;
      }

      total++;

      if (hasUser && hasCross) {
        const result = compareAuthors(userVal, crossVal);

        if (result.status === 'match') {
          matched++;
        } else if (result.fuzzyMatches.length > 0 && result.missingInUser.length === 0 && result.extraInUser.length === 0) {
          matched += 0.8;
        }
      }

      return;
    }

    let compU = normalizeValue(userVal);
    let compC = normalizeValue(crossVal);

    if (key === 'type') {
      compU = normalizeType(compU);
      compC = normalizeType(compC);
    } else {
      compU = comparable(compU);
      compC = comparable(compC);
    }

    const isEmptyU = compU === '';
    const isEmptyC = compC === '';

    if (isEmptyU && isEmptyC) {
      return;
    }

    total++;

    if (!isEmptyU && !isEmptyC && compU === compC) {
      matched++;
    }
  });

  if (total === 0) {
    return 0;
  }

  return Math.round((matched / total) * 100);
}

function renderMatchScore(score) {
  const scoreBox = document.getElementById('crossrefMatchScore');
  if (!scoreBox) return;

  let badgeClass = 'bg-danger';

  if (score >= 80) {
    badgeClass = 'bg-success';
  } else if (score >= 50) {
    badgeClass = 'bg-warning text-dark';
  }

  scoreBox.innerHTML = `
    <span class="me-2">Match Score:</span>
							<span class="badge ${badgeClass}">${score}%</span>
  `;
}

<?php

function extractSuffix($uri) {
    if (strpos($uri, '#') !== false) {
        return substr(strrchr($uri, '#'), 1);
    } elseif (strpos($uri, '/') !== false) {
        return substr(strrchr($uri, '/'), 1);
    }
    return $uri;
}

?>
						</script>
					</body>



<?php
include BASE_PATH . 'modals/pdf_modal.php';
include BASE_PATH . 'modals/reject_pub_modal.php';
include BASE_PATH . 'modals/crossref_modal.php';
?>
				</html>
