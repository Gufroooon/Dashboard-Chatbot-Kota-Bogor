# TODO: Include Year and Source in Knowledge Output

## Tasks
- [ ] Modify KnowledgeController store method to prepend content with year and source metadata
- [ ] Modify KnowledgeController quickAdd method to prepend content with year and source metadata
- [ ] Test by adding new knowledge and verifying AI responses include the metadata

## Information Gathered
- Knowledge entries store metadata (tahun, sumber) separately from content
- Content is sent to n8n for embedding and retrieval
- By prepending metadata to content, AI responses will naturally include year and source

## Plan
- Update store method: If tahun or sumber present, prepend content with "Berdasarkan data dari [sumber] pada tahun [tahun]: "
- Update quickAdd method similarly
- Ensure existing functionality remains unchanged

## Followup Steps
- Add new knowledge via dashboard
- Query the chatbot and check if responses include the year/source phrase
- If needed, adjust the prepend format for better readability
