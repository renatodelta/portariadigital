export async function onRequest(context) {
    const { request, env } = context;
    const url = new URL(request.url);
    const action = url.searchParams.get('action') || '';
    
    // Set headers
    const corsHeaders = {
        "Access-Control-Allow-Origin": "*",
        "Access-Control-Allow-Methods": "GET, HEAD, POST, OPTIONS",
        "Access-Control-Allow-Headers": "Content-Type",
        "Content-Type": "application/json; charset=UTF-8"
    };

    // Handle OPTIONS request (CORS)
    if (request.method === "OPTIONS") {
        return new Response(null, { headers: corsHeaders });
    }

    if (!env.DB) {
        return new Response(JSON.stringify({ error: "Cloudflare D1 database binding 'DB' not found." }), {
            status: 500,
            headers: corsHeaders
        });
    }

    // Clean old offline status (older than 6 seconds)
    try {
        const sixSecsAgo = Math.floor(Date.now() / 1000) - 6;
        await env.DB.prepare("UPDATE units SET status = 'offline' WHERE last_seen < ? AND status = 'online'")
            .bind(sixSecsAgo)
            .run();
    } catch (e) {
        console.error("Failed to clean offline status:", e);
    }

    try {
        switch (action) {
            case 'ping': {
                const unit_id = url.searchParams.get('unit_id') || '';
                const peer_id = url.searchParams.get('peer_id') || '';
                if (unit_id) {
                    const now = Math.floor(Date.now() / 1000);
                    await env.DB.prepare("UPDATE units SET peer_id = ?, last_seen = ?, status = 'online' WHERE id = ?")
                        .bind(peer_id, now, unit_id)
                        .run();
                    return new Response(JSON.stringify({ status: 'success' }), { headers: corsHeaders });
                }
                return new Response(JSON.stringify({ status: 'error', message: 'Missing unit_id' }), { status: 400, headers: corsHeaders });
            }

            case 'list_units': {
                const { results } = await env.DB.prepare("SELECT * FROM units").all();
                return new Response(JSON.stringify(results), { headers: corsHeaders });
            }

            case 'add_delivery': {
                if (request.method !== 'POST') return new Response("Method Not Allowed", { status: 405 });
                const formData = await request.formData();
                const unit_id = formData.get('unit_id') || '';
                const description = formData.get('description') || '';
                const courier = formData.get('courier') || '';
                
                if (unit_id && description && courier) {
                    const now = Math.floor(Date.now() / 1000);
                    await env.DB.prepare("INSERT INTO deliveries (unit_id, description, courier, created_at) VALUES (?, ?, ?, ?)")
                        .bind(unit_id, description, courier, now)
                        .run();
                    return new Response(JSON.stringify({ status: 'success' }), { headers: corsHeaders });
                }
                return new Response(JSON.stringify({ status: 'error', message: 'Missing params' }), { status: 400, headers: corsHeaders });
            }

            case 'list_deliveries': {
                const unit_id = url.searchParams.get('unit_id') || '';
                let query = "SELECT * FROM deliveries ORDER BY created_at DESC";
                let stmt;
                if (unit_id) {
                    stmt = env.DB.prepare("SELECT * FROM deliveries WHERE unit_id = ? ORDER BY created_at DESC").bind(unit_id);
                } else {
                    stmt = env.DB.prepare(query);
                }
                const { results } = await stmt.all();
                return new Response(JSON.stringify(results), { headers: corsHeaders });
            }

            case 'confirm_delivery': {
                if (request.method !== 'POST') return new Response("Method Not Allowed", { status: 405 });
                const formData = await request.formData();
                const id = formData.get('id') || '';
                if (id) {
                    await env.DB.prepare("UPDATE deliveries SET status = 'delivered' WHERE id = ?").bind(id).run();
                    return new Response(JSON.stringify({ status: 'success' }), { headers: corsHeaders });
                }
                return new Response(JSON.stringify({ status: 'error', message: 'Missing ID' }), { status: 400, headers: corsHeaders });
            }

            case 'add_visit': {
                if (request.method !== 'POST') return new Response("Method Not Allowed", { status: 405 });
                const formData = await request.formData();
                const unit_id = formData.get('unit_id') || '';
                const visitor_name = formData.get('visitor_name') || '';
                
                if (unit_id && visitor_name) {
                    const now = Math.floor(Date.now() / 1000);
                    await env.DB.prepare("INSERT INTO visits (unit_id, visitor_name, created_at) VALUES (?, ?, ?)")
                        .bind(unit_id, visitor_name, now)
                        .run();
                    return new Response(JSON.stringify({ status: 'success' }), { headers: corsHeaders });
                }
                return new Response(JSON.stringify({ status: 'error', message: 'Missing params' }), { status: 400, headers: corsHeaders });
            }

            case 'list_visits': {
                const unit_id = url.searchParams.get('unit_id') || '';
                let stmt;
                if (unit_id) {
                    stmt = env.DB.prepare("SELECT * FROM visits WHERE unit_id = ? ORDER BY created_at DESC").bind(unit_id);
                } else {
                    stmt = env.DB.prepare("SELECT * FROM visits ORDER BY created_at DESC");
                }
                const { results } = await stmt.all();
                return new Response(JSON.stringify(results), { headers: corsHeaders });
            }

            case 'trigger_call': {
                if (request.method !== 'POST') return new Response("Method Not Allowed", { status: 405 });
                const formData = await request.formData();
                const unit_id = formData.get('unit_id') || '';
                if (unit_id) {
                    const now = Math.floor(Date.now() / 1000);
                    const info = await env.DB.prepare("INSERT INTO call_signals (unit_id, status, created_at) VALUES (?, 'ringing', ?)")
                        .bind(unit_id, now)
                        .run();
                    return new Response(JSON.stringify({ status: 'success', call_id: info.meta.last_row_id }), { headers: corsHeaders });
                }
                return new Response(JSON.stringify({ status: 'error', message: 'Missing unit_id' }), { status: 400, headers: corsHeaders });
            }

            case 'update_call': {
                if (request.method !== 'POST') return new Response("Method Not Allowed", { status: 405 });
                const formData = await request.formData();
                const call_id = formData.get('call_id') || '';
                const status = formData.get('status') || '';
                if (call_id && status) {
                    await env.DB.prepare("UPDATE call_signals SET status = ? WHERE id = ?").bind(status, call_id).run();
                    return new Response(JSON.stringify({ status: 'success' }), { headers: corsHeaders });
                }
                return new Response(JSON.stringify({ status: 'error', message: 'Missing params' }), { status: 400, headers: corsHeaders });
            }

            // Real-time polling replacement for Server-Sent Events (SSE)
            case 'get_updates': {
                const unit_id = url.searchParams.get('unit_id') || '';
                const last_delivery_id = parseInt(url.searchParams.get('last_delivery_id') || '0', 10);
                const last_visit_id = parseInt(url.searchParams.get('last_visit_id') || '0', 10);
                const last_call_id = parseInt(url.searchParams.get('last_call_id') || '0', 10);
                
                if (!unit_id) {
                    return new Response(JSON.stringify({ error: 'Missing unit_id' }), { status: 400, headers: corsHeaders });
                }

                // Check for new pending deliveries
                const deliveries = await env.DB.prepare("SELECT * FROM deliveries WHERE unit_id = ? AND id > ? AND status = 'pending' ORDER BY id ASC")
                    .bind(unit_id, last_delivery_id)
                    .all();

                // Check for new visits
                const visits = await env.DB.prepare("SELECT * FROM visits WHERE unit_id = ? AND id > ? AND status = 'pending' ORDER BY id ASC")
                    .bind(unit_id, last_visit_id)
                    .all();

                // Check for active calls (within the last 20 seconds to prevent old calls)
                const now = Math.floor(Date.now() / 1000);
                const calls = await env.DB.prepare("SELECT * FROM call_signals WHERE unit_id = ? AND id > ? AND status = 'ringing' AND created_at > ? ORDER BY id ASC")
                    .bind(unit_id, last_call_id, now - 20)
                    .all();

                return new Response(JSON.stringify({
                    deliveries: deliveries.results || [],
                    visits: visits.results || [],
                    calls: calls.results || []
                }), { headers: corsHeaders });
            }

            default:
                return new Response(JSON.stringify({ error: 'Invalid action' }), { status: 404, headers: corsHeaders });
        }
    } catch (err) {
        return new Response(JSON.stringify({ error: err.message }), { status: 500, headers: corsHeaders });
    }
}
