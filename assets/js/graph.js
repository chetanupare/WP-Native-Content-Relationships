/**
 * Relationship Graph Visualization
 *
 * @package Native Content Relationships
 * @since 1.2.0
 */

(function ($) {
    'use strict';

    var NaticoreGraph = {
        canvas: null,
        ctx: null,
        nodes: [],
        edges: [],
        width: 0,
        height: 0,
        selectedNode: null,
        dragNode: null,
        offsetX: 0,
        offsetY: 0,
        scale: 1,
        panX: 0,
        panY: 0,
        isDragging: false,
        isPanning: false,
        lastMouseX: 0,
        lastMouseY: 0,

        init: function () {
            this.canvas = document.getElementById('naticore-graph-canvas');
            if (!this.canvas) return;

            this.ctx = this.canvas.getContext('2d');
            this.resize();
            this.bindEvents();
            this.loadData();
        },

        resize: function () {
            var container = $('#naticore-graph-container');
            this.width = container.width();
            this.height = Math.max(500, container.height());
            this.canvas.width = this.width;
            this.canvas.height = this.height;
            this.canvas.style.display = 'block';
            $('#naticore-graph-loading').hide();
        },

        bindEvents: function () {
            var self = this;

            // Mouse events
            this.canvas.addEventListener('mousedown', function (e) {
                self.handleMouseDown(e);
            });

            this.canvas.addEventListener('mousemove', function (e) {
                self.handleMouseMove(e);
            });

            this.canvas.addEventListener('mouseup', function (e) {
                self.handleMouseUp(e);
            });

            this.canvas.addEventListener('wheel', function (e) {
                self.handleWheel(e);
            });

            // Click events
            $('#naticore-graph-refresh').on('click', function () {
                self.loadData();
            });

            $('#naticore-graph-filter, #naticore-graph-limit').on('change', function () {
                self.loadData();
            });
        },

        handleMouseDown: function (e) {
            var rect = this.canvas.getBoundingClientRect();
            var x = (e.clientX - rect.left) / this.scale - this.panX;
            var y = (e.clientY - rect.top) / this.scale - this.panY;

            // Check if clicking on a node
            for (var i = 0; i < this.nodes.length; i++) {
                var node = this.nodes[i];
                var dx = x - node.x;
                var dy = y - node.y;
                if (dx * dx + dy * dy < 400) { // 20px radius
                    this.dragNode = node;
                    this.offsetX = dx;
                    this.offsetY = dy;
                    this.selectedNode = node;
                    this.draw();
                    return;
                }
            }

            // Start panning
            this.isPanning = true;
            this.lastMouseX = e.clientX;
            this.lastMouseY = e.clientY;
        },

        handleMouseMove: function (e) {
            if (this.dragNode) {
                var rect = this.canvas.getBoundingClientRect();
                this.dragNode.x = (e.clientX - rect.left) / this.scale - this.panX - this.offsetX;
                this.dragNode.y = (e.clientY - rect.top) / this.scale - this.panY - this.offsetY;
                this.draw();
            } else if (this.isPanning) {
                var dx = e.clientX - this.lastMouseX;
                var dy = e.clientY - this.lastMouseY;
                this.panX += dx / this.scale;
                this.panY += dy / this.scale;
                this.lastMouseX = e.clientX;
                this.lastMouseY = e.clientY;
                this.draw();
            }
        },

        handleMouseUp: function (e) {
            if (this.dragNode) {
                // Check if it was a click (not a drag)
                if (Math.abs(e.clientX - this.lastMouseX) < 5 && Math.abs(e.clientY - this.lastMouseY) < 5) {
                    if (this.dragNode.url) {
                        window.open(this.dragNode.url, '_blank');
                    }
                }
            }
            this.dragNode = null;
            this.isPanning = false;
        },

        handleWheel: function (e) {
            e.preventDefault();
            var delta = e.deltaY > 0 ? 0.9 : 1.1;
            this.scale *= delta;
            this.scale = Math.max(0.3, Math.min(3, this.scale));
            this.draw();
        },

        loadData: function () {
            var self = this;
            var filter = $('#naticore-graph-filter').val();
            var limit = $('#naticore-graph-limit').val();

            $('#naticore-graph-loading').show();
            this.canvas.style.display = 'none';

            $.ajax({
                url: naticoreGraph.ajaxUrl,
                type: 'POST',
                data: {
                    action: 'naticore_get_graph_data',
                    nonce: naticoreGraph.nonce,
                    type: filter,
                    limit: limit
                },
                success: function (response) {
                    if (response.success && response.data.nodes.length > 0) {
                        self.nodes = response.data.nodes;
                        self.edges = response.data.edges;
                        self.layoutGraph();
                        self.draw();
                    } else {
                        $('#naticore-graph-loading').html(
                            '<p style="color: #646970; padding: 40px;">' + naticoreGraph.i18n.noData + '</p>'
                        );
                    }
                },
                error: function () {
                    $('#naticore-graph-loading').html(
                        '<p style="color: #d63638; padding: 40px;">' + naticoreGraph.i18n.error + '</p>'
                    );
                }
            });
        },

        layoutGraph: function () {
            // Simple force-directed layout
            var centerX = this.width / 2;
            var centerY = this.height / 2;
            var radius = Math.min(this.width, this.height) * 0.35;

            // Initial circular layout
            for (var i = 0; i < this.nodes.length; i++) {
                var angle = (2 * Math.PI * i) / this.nodes.length;
                this.nodes[i].x = centerX + radius * Math.cos(angle);
                this.nodes[i].y = centerY + radius * Math.sin(angle);
                this.nodes[i].vx = 0;
                this.nodes[i].vy = 0;
            }

            // Run force simulation
            for (var iter = 0; iter < 100; iter++) {
                this.simulateForces();
            }
        },

        simulateForces: function () {
            var k = 0.01; // Spring constant
            var repulsion = 5000; // Repulsion strength
            var damping = 0.9;

            // Repulsion between nodes
            for (var i = 0; i < this.nodes.length; i++) {
                for (var j = i + 1; j < this.nodes.length; j++) {
                    var dx = this.nodes[j].x - this.nodes[i].x;
                    var dy = this.nodes[j].y - this.nodes[i].y;
                    var dist = Math.sqrt(dx * dx + dy * dy) || 1;
                    var force = repulsion / (dist * dist);

                    var fx = (dx / dist) * force;
                    var fy = (dy / dist) * force;

                    this.nodes[i].vx -= fx;
                    this.nodes[i].vy -= fy;
                    this.nodes[j].vx += fx;
                    this.nodes[j].vy += fy;
                }
            }

            // Attraction along edges
            for (var e = 0; e < this.edges.length; e++) {
                var edge = this.edges[e];
                var from = this.findNode(edge.from);
                var to = this.findNode(edge.to);

                if (from && to) {
                    var dx = to.x - from.x;
                    var dy = to.y - from.y;
                    var dist = Math.sqrt(dx * dx + dy * dy) || 1;
                    var force = k * (dist - 100);

                    var fx = (dx / dist) * force;
                    var fy = (dy / dist) * force;

                    from.vx += fx;
                    from.vy += fy;
                    to.vx -= fx;
                    to.vy -= fy;
                }
            }

            // Center gravity
            var centerX = this.width / 2;
            var centerY = this.height / 2;
            for (var i = 0; i < this.nodes.length; i++) {
                this.nodes[i].vx += (centerX - this.nodes[i].x) * 0.001;
                this.nodes[i].vy += (centerY - this.nodes[i].y) * 0.001;
            }

            // Apply velocities
            for (var i = 0; i < this.nodes.length; i++) {
                this.nodes[i].vx *= damping;
                this.nodes[i].vy *= damping;
                this.nodes[i].x += this.nodes[i].vx;
                this.nodes[i].y += this.nodes[i].vy;

                // Keep within bounds
                this.nodes[i].x = Math.max(50, Math.min(this.width - 50, this.nodes[i].x));
                this.nodes[i].y = Math.max(50, Math.min(this.height - 50, this.nodes[i].y));
            }
        },

        findNode: function (id) {
            for (var i = 0; i < this.nodes.length; i++) {
                if (this.nodes[i].id === id) {
                    return this.nodes[i];
                }
            }
            return null;
        },

        draw: function () {
            this.ctx.clearRect(0, 0, this.width, this.height);
            this.ctx.save();
            this.ctx.scale(this.scale, this.scale);
            this.ctx.translate(this.panX, this.panY);

            // Draw edges
            for (var i = 0; i < this.edges.length; i++) {
                this.drawEdge(this.edges[i]);
            }

            // Draw nodes
            for (var i = 0; i < this.nodes.length; i++) {
                this.drawNode(this.nodes[i]);
            }

            this.ctx.restore();
        },

        drawEdge: function (edge) {
            var from = this.findNode(edge.from);
            var to = this.findNode(edge.to);

            if (!from || !to) return;

            this.ctx.beginPath();
            this.ctx.moveTo(from.x, from.y);
            this.ctx.lineTo(to.x, to.y);
            this.ctx.strokeStyle = '#dcdcde';
            this.ctx.lineWidth = 1;
            this.ctx.stroke();

            // Draw arrow
            var angle = Math.atan2(to.y - from.y, to.x - from.x);
            var arrowLen = 10;
            var arrowX = to.x - 20 * Math.cos(angle);
            var arrowY = to.y - 20 * Math.sin(angle);

            this.ctx.beginPath();
            this.ctx.moveTo(arrowX, arrowY);
            this.ctx.lineTo(
                arrowX - arrowLen * Math.cos(angle - Math.PI / 6),
                arrowY - arrowLen * Math.sin(angle - Math.PI / 6)
            );
            this.ctx.lineTo(
                arrowX - arrowLen * Math.cos(angle + Math.PI / 6),
                arrowY - arrowLen * Math.sin(angle + Math.PI / 6)
            );
            this.ctx.closePath();
            this.ctx.fillStyle = '#dcdcde';
            this.ctx.fill();
        },

        drawNode: function (node) {
            var color = this.getNodeColor(node.type);
            var radius = 20;

            // Highlight selected node
            if (this.selectedNode && this.selectedNode.id === node.id) {
                this.ctx.beginPath();
                this.ctx.arc(node.x, node.y, radius + 5, 0, Math.PI * 2);
                this.ctx.fillStyle = 'rgba(34, 113, 177, 0.2)';
                this.ctx.fill();
            }

            // Draw node circle
            this.ctx.beginPath();
            this.ctx.arc(node.x, node.y, radius, 0, Math.PI * 2);
            this.ctx.fillStyle = color;
            this.ctx.fill();
            this.ctx.strokeStyle = '#fff';
            this.ctx.lineWidth = 2;
            this.ctx.stroke();

            // Draw label
            this.ctx.fillStyle = '#1d2327';
            this.ctx.font = '12px -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif';
            this.ctx.textAlign = 'center';
            this.ctx.textBaseline = 'top';
            this.ctx.fillText(node.label, node.x, node.y + radius + 5);
        },

        getNodeColor: function (type) {
            var colors = {
                'post': '#2271b1',
                'page': '#d63638',
                'user': '#00a32a',
                'product': '#996800'
            };
            return colors[type] || '#646970';
        }
    };

    $(document).ready(function () {
        NaticoreGraph.init();
    });

})(jQuery);
