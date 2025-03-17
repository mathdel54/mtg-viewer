export async function fetchAllCards(id) {
    const response = await fetch(`/api/card/all?page=${id}`);
    if (!response.ok) throw new Error('Failed to fetch cards');
    const result = await response.json();
    return result;
}

export async function fetchCard(uuid) {
    const response = await fetch(`/api/card/${uuid}`);
    if (response.status === 404) return null;
    if (!response.ok) throw new Error('Failed to fetch card');
    const card = await response.json();
    card.text = card.text.replaceAll('\\n', '\n');
    return card;
}

export async function fetchCardsByName(name) {
    const response = await fetch(`/api/card/name/${name}`);
    if (!response.ok) throw new Error('Failed to fetch cards');
    const result = await response.json();
    return result;
}

export async function fetchAllSetCodes() {
    const response = await fetch('/api/card/setcodes');
    if (!response.ok) throw new Error('Failed to fetch cards');
    const result = await response.json();
    return result;
}
