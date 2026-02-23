function openModal(id) {
    fetch(`/equipe/${id}/json`)
        .then(res => res.json())
        .then(data => {
            document.getElementById('modalTeamName').innerText = data.name;
            document.getElementById('modalTeamId').innerText = data.id;
            document.getElementById('modalOwner').innerText = data.owner || 'N/A';
            document.getElementById('modalTournament').innerText = data.tournaments ? data.tournaments.join(', ') : 'No tournaments';
            document.getElementById('modalMembers').innerText = data.memberCount || 0;
            document.getElementById('modalJoinBtn').href = `/equipe/${id}/join`;

            document.getElementById('teamModal').style.display = 'block';
        })
        .catch(err => {
            console.error('Erreur:', err);
            document.getElementById('modalTeamName').innerText = 'Erreur de chargement';
        });
}

function closeModal() {
    document.getElementById('teamModal').style.display = 'none';
}

window.onclick = function (e) {
    const modal = document.getElementById('teamModal');
    if (e.target === modal) {
        closeModal();
    }
};
